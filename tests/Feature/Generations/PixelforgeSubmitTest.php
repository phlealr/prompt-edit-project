<?php

use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Exceptions\PromptRejected;
use App\ImageProviders\Exceptions\ProviderBusy;
use App\ImageProviders\Exceptions\ProviderUnreachable;
use App\ImageProviders\Exceptions\SubmissionRefused;
use App\ImageProviders\Pixelforge\PixelforgeImageProvider;
use App\Models\Generation;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.pixelforge.base_url', 'https://pixelforge.test/v1');
    config()->set('services.pixelforge.api_key', 'pf_test_key');
    config()->set('services.pixelforge.pricing_cache_seconds', 300);
});

it('creates the generation with its UUID as idempotency key and its id in the metadata', function () {
    Http::fake(['pixelforge.test/v1/generations' => Http::response(pixelforgeGeneration(), 202)]);
    $generation = Generation::factory()->create([
        'uuid' => '6f1c2a9e-3b7d-4e0a-9c55-2d8f1b4e7a10',
        'prompt' => 'A lighthouse on a cliff at dawn, oil painting',
        'size' => Size::Landscape,
        'quality' => Quality::High,
        'reference_images' => ['https://example.com/style.png'],
    ]);

    app(PixelforgeImageProvider::class)->submit($generation);

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://pixelforge.test/v1/generations'
        && $request->hasHeader('Authorization', 'Bearer pf_test_key')
        && $request->hasHeader('Idempotency-Key', '6f1c2a9e-3b7d-4e0a-9c55-2d8f1b4e7a10')
        && $request->data() === [
            'prompt' => 'A lighthouse on a cliff at dawn, oil painting',
            'size' => '1536x1024',
            'quality' => 'high',
            'reference_images' => ['https://example.com/style.png'],
            'metadata' => ['generation_id' => (string) $generation->id],
        ]);
});

it('turns the accepted generation into a provider update', function (string $status, GenerationStatus $expected, ?FailureReason $reason) {
    Http::fake(['pixelforge.test/v1/generations' => Http::response(pixelforgeGeneration(['status' => $status]), 202)]);

    $update = app(PixelforgeImageProvider::class)->submit(Generation::factory()->create());

    expect($update->providerId)->toBe('gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K')
        ->and($update->status)->toBe($expected)
        ->and($update->failureReason)->toBe($reason)
        ->and($update->rawResponse)->toContain('"id":"gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K"');
})->with([
    'accepted' => ['queued', GenerationStatus::Queued, null],
    'idempotent replay, already processing' => ['processing', GenerationStatus::Processing, null],
    'idempotent replay, already succeeded' => ['succeeded', GenerationStatus::Succeeded, null],
    'idempotent replay, already failed' => ['failed', GenerationStatus::Failed, FailureReason::ProviderFailed],
]);

it('fetches the current rates bypassing the cache', function () {
    Http::fake(['pixelforge.test/v1/pricing' => Http::sequence()
        ->push(pixelforgePricing())
        ->push(pixelforgePricing(['output_tokens' => '80.00'])),
    ]);
    $provider = app(PixelforgeImageProvider::class);

    $provider->estimate('A cat', Size::Square, Quality::Low, []); // caches $40.00
    $rates = $provider->currentRates();

    expect($rates->outputTokens->nanodollars())->toBe(80_000_000_000);
    Http::assertSentCount(2);
});

/**
 * A Pixelforge error body, as in docs/spec/pixelforge-api.md#errors.
 */
function pixelforgeError(string $type, string $code, string $message = 'The request was refused.'): array
{
    return ['error' => ['type' => $type, 'code' => $code, 'message' => $message]];
}

it('turns a content policy rejection into our PromptRejected exception', function () {
    Http::fake(['pixelforge.test/v1/generations' => Http::response(
        pixelforgeError('invalid_request_error', 'content_policy_violation', 'The prompt was rejected.'), 400,
    )]);

    app(PixelforgeImageProvider::class)->submit(Generation::factory()->create());
})->throws(PromptRejected::class);

it('turns errors waiting cannot fix into our SubmissionRefused exception, naming the provider\'s answer', function (int $status, string $type, string $code) {
    Http::fake(['pixelforge.test/v1/generations' => Http::response(pixelforgeError($type, $code), $status)]);

    expect(fn () => app(PixelforgeImageProvider::class)->submit(Generation::factory()->create()))
        ->toThrow(SubmissionRefused::class, "{$status} {$code}");
})->with([
    'daily limit' => [429, 'rate_limit_error', 'daily_limit_exceeded'],
    'invalid API key' => [401, 'authentication_error', 'invalid_api_key'],
    'validation failed' => [422, 'invalid_request_error', 'validation_failed'],
    'idempotency key reused' => [409, 'idempotency_error', 'idempotency_key_reused'],
]);

it('turns answers waiting can fix into ProviderBusy, with the provider\'s Retry-After', function (int $status, string $type, string $code, string $retryAfter) {
    Http::fake(['pixelforge.test/v1/generations' => Http::response(pixelforgeError($type, $code), $status, ['Retry-After' => $retryAfter])]);

    try {
        app(PixelforgeImageProvider::class)->submit(Generation::factory()->create());
        $this->fail('ProviderBusy was not thrown.');
    } catch (ProviderBusy $exception) {
        expect($exception->retryAfterSeconds)->toBe((int) $retryAfter);
    }
})->with([
    'rate limit' => [429, 'rate_limit_error', 'rate_limit_exceeded', '17'],
    'concurrency limit' => [429, 'rate_limit_error', 'concurrency_limit_exceeded', '5'],
    'overloaded' => [503, 'api_error', 'overloaded', '2'],
]);

it('turns a network error or timeout into ProviderUnreachable', function () {
    Http::fake(['pixelforge.test/*' => Http::failedConnection()]);

    app(PixelforgeImageProvider::class)->submit(Generation::factory()->create());
})->throws(ProviderUnreachable::class);

it('turns a server error into ProviderUnreachable, since it may have created the generation', function () {
    Http::fake(['pixelforge.test/v1/generations' => Http::response(pixelforgeError('api_error', 'internal_error'), 500)]);

    app(PixelforgeImageProvider::class)->submit(Generation::factory()->create());
})->throws(ProviderUnreachable::class);

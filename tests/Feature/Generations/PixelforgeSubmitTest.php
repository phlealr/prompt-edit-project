<?php

use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
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

it('turns the accepted generation into a provider update', function (string $status, GenerationStatus $expected) {
    Http::fake(['pixelforge.test/v1/generations' => Http::response(pixelforgeGeneration(['status' => $status]), 202)]);

    $update = app(PixelforgeImageProvider::class)->submit(Generation::factory()->create());

    expect($update->providerId)->toBe('gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K')
        ->and($update->status)->toBe($expected)
        ->and($update->rawResponse)->toContain('"id":"gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K"');
})->with([
    'accepted' => ['queued', GenerationStatus::Queued],
    'idempotent replay, already processing' => ['processing', GenerationStatus::Processing],
    'idempotent replay, already succeeded' => ['succeeded', GenerationStatus::Succeeded],
    'idempotent replay, already failed' => ['failed', GenerationStatus::Failed],
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

<?php

use App\Enums\GenerationStatus;
use App\ImageProviders\Exceptions\GenerationNotFound;
use App\ImageProviders\Exceptions\ProviderBusy;
use App\ImageProviders\Exceptions\ProviderUnreachable;
use App\ImageProviders\Pixelforge\PixelforgeImageProvider;
use App\Models\Generation;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.pixelforge.base_url', 'https://pixelforge.test/v1');
    config()->set('services.pixelforge.api_key', 'pf_test_key');
});

it('fetches a generation by the provider\'s id and turns it into a provider update', function () {
    Http::fake(['pixelforge.test/v1/generations/gen_pf_1' => Http::response(pixelforgeGeneration(['id' => 'gen_pf_1', 'status' => 'processing']))]);

    $update = app(PixelforgeImageProvider::class)->fetch(Generation::factory()->create(['provider_id' => 'gen_pf_1']));

    expect($update->status)->toBe(GenerationStatus::Processing)
        ->and($update->providerId)->toBe('gen_pf_1');
    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && $request->hasHeader('Authorization', 'Bearer pf_test_key'));
});

it('maps the provider\'s answers when fetching', function (int $status, string $code, string $exception) {
    Http::fake(['pixelforge.test/*' => Http::response(
        ['error' => ['type' => 'x', 'code' => $code, 'message' => 'No.']], $status, ['Retry-After' => '3'],
    )]);

    expect(fn () => app(PixelforgeImageProvider::class)->fetch(Generation::factory()->create(['provider_id' => 'gen_pf_1'])))
        ->toThrow($exception);
})->with([
    'not found' => [404, 'not_found', GenerationNotFound::class],
    'rate limited' => [429, 'rate_limit_exceeded', ProviderBusy::class],
    'overloaded' => [503, 'overloaded', ProviderUnreachable::class],
    'server error' => [500, 'internal_error', ProviderUnreachable::class],
]);

it('turns a network error while fetching into ProviderUnreachable', function () {
    Http::fake(['pixelforge.test/*' => Http::failedConnection()]);

    app(PixelforgeImageProvider::class)->fetch(Generation::factory()->create(['provider_id' => 'gen_pf_1']));
})->throws(ProviderUnreachable::class);

it('treats an answer it cannot read as unreachable, to try again next run', function () {
    Http::fake(['pixelforge.test/*' => Http::response('<html>Bad gateway</html>', 200)]);

    app(PixelforgeImageProvider::class)->fetch(Generation::factory()->create(['provider_id' => 'gen_pf_1']));
})->throws(ProviderUnreachable::class);

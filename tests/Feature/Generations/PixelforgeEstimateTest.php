<?php

use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\Pixelforge\PixelforgeImageProvider;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.pixelforge.base_url', 'https://pixelforge.test/v1');
    config()->set('services.pixelforge.pricing_cache_seconds', 300);
});

it('estimates the worked example at the published rates', function () {
    Http::fake(['pixelforge.test/v1/pricing' => Http::response(pixelforgePricing())]);

    $estimate = app(PixelforgeImageProvider::class)->estimate(str_repeat('a', 400), Size::Landscape, Quality::High, ['https://example.com/a.png', 'https://example.com/b.png']);

    // Worked example maximum: provider cost $0.373875000, charge $0.467343750.
    expect($estimate->maximum->nanodollars())->toBe(467_343_750)
        // Minimum: 85 text, 1,530 image, 6,450 output, 1,000 reasoning tokens
        // = $0.286225000 provider cost, charge $0.357781250.
        ->and($estimate->minimum->nanodollars())->toBe(357_781_250)
        ->and($estimate->rates->inputTextTokens->nanodollars())->toBe(5_000_000_000)
        ->and($estimate->rates->inputImageTokens->nanodollars())->toBe(10_000_000_000)
        ->and($estimate->rates->outputTokens->nanodollars())->toBe(40_000_000_000)
        ->and($estimate->rates->reasoningTokens->nanodollars())->toBe(12_500_000_000);
});

it('counts a duplicated reference image URL twice', function () {
    Http::fake(['pixelforge.test/v1/pricing' => Http::response(pixelforgePricing())]);

    $estimate = app(PixelforgeImageProvider::class)
        ->estimate('', Size::Square, Quality::Low, ['https://example.com/a.png', 'https://example.com/a.png']);

    // 2 × 765 × 10,000 + 280 × 40,000 + 500 × 12,500 = 32,750,000 → charge 40,937,500.
    expect($estimate->maximum->nanodollars())->toBe(40_937_500);
});

it('rounds text tokens outwards: down for the minimum, up for the maximum', function () {
    Http::fake(['pixelforge.test/v1/pricing' => Http::response(pixelforgePricing())]);

    // 12 characters = 3 text tokens: minimum floor(2.55) = 2, maximum ceil(3.45) = 4.
    $estimate = app(PixelforgeImageProvider::class)->estimate(str_repeat('a', 12), Size::Square, Quality::Low, []);

    // Minimum: 2 × 5,000 + 280 × 40,000 + 0 reasoning = 11,210,000 → charge 14,012,500.
    // Maximum: 4 × 5,000 + 280 × 40,000 + 500 × 12,500 = 17,470,000 → charge 21,837,500.
    expect($estimate->minimum->nanodollars())->toBe(14_012_500)
        ->and($estimate->maximum->nanodollars())->toBe(21_837_500);
});

it('counts text tokens by Unicode characters, not bytes', function () {
    Http::fake(['pixelforge.test/v1/pricing' => Http::response(pixelforgePricing())]);

    // 8 characters of 2 bytes each: 2 text tokens (bytes would give 4), maximum ceil(2.3) = 3.
    $estimate = app(PixelforgeImageProvider::class)->estimate(str_repeat('é', 8), Size::Square, Quality::Low, []);

    // 3 × 5,000 + 280 × 40,000 + 500 × 12,500 = 17,465,000 → charge 21,831,250.
    expect($estimate->maximum->nanodollars())->toBe(21_831_250);
});

it('caches the rates for the configured time and uses new ones after it expires', function () {
    Http::fake(['pixelforge.test/v1/pricing' => Http::sequence()
        ->push(pixelforgePricing())
        ->push(pixelforgePricing(['output_tokens' => '80.00'])),
    ]);
    $provider = app(PixelforgeImageProvider::class);

    // Low, 1024x1024, empty prompt: 280 × 40,000 + 500 × 12,500 = 17,450,000 → 21,812,500.
    expect($provider->estimate('', Size::Square, Quality::Low, [])->maximum->nanodollars())->toBe(21_812_500);

    $this->travel(299)->seconds();
    expect($provider->estimate('', Size::Square, Quality::Low, [])->maximum->nanodollars())->toBe(21_812_500);

    // At $80.00: 280 × 80,000 + 500 × 12,500 = 28,650,000 → 35,812,500.
    $this->travel(2)->seconds();
    expect($provider->estimate('', Size::Square, Quality::Low, [])->maximum->nanodollars())->toBe(35_812_500);
    Http::assertSentCount(2);
});

it('fails with our own exception when pricing cannot be fetched and nothing is cached', function (Closure $fake) {
    $fake();

    app(PixelforgeImageProvider::class)->estimate('a cat', Size::Square, Quality::Medium, []);
})->with([
    'connection error' => [fn () => Http::fake(['pixelforge.test/*' => Http::failedConnection()])],
    'server error' => [fn () => Http::fake(['pixelforge.test/*' => Http::response(['error' => []], 500)])],
    'body that is not JSON' => [fn () => Http::fake(['pixelforge.test/*' => Http::response('<html></html>')])],
    'missing rate' => [fn () => Http::fake(['pixelforge.test/*' => Http::response(
        array_replace_recursive(pixelforgePricing(), ['rates' => ['output_tokens' => null]]),
    )])],
    'rate as a float' => [fn () => Http::fake(['pixelforge.test/*' => Http::response(
        array_replace_recursive(pixelforgePricing(), ['rates' => ['output_tokens' => 40.5]]),
    )])],
    'variance other than the 15% of ADR 0024' => [fn () => Http::fake(['pixelforge.test/*' => Http::response(
        array_replace_recursive(pixelforgePricing(), ['tokens' => ['text_token_variance' => 0.2]]),
    )])],
    'rate too large to hold' => [fn () => Http::fake(['pixelforge.test/*' => Http::response(
        array_replace_recursive(pixelforgePricing(), ['rates' => ['output_tokens' => '99999999999.00']]),
    )])],
    'missing output tokens for a size' => [fn () => Http::fake(['pixelforge.test/*' => Http::response(
        array_replace_recursive(pixelforgePricing(), ['tokens' => ['output' => ['medium' => ['1024x1024' => null]]]]),
    )])],
])->throws(PricingUnavailable::class);

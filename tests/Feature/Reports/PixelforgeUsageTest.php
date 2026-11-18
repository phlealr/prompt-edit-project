<?php

use App\ImageProviders\Exceptions\UsageUnavailable;
use App\ImageProviders\Pixelforge\PixelforgeImageProvider;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.pixelforge.base_url', 'https://pixelforge.test/v1');
    config()->set('services.pixelforge.api_key', 'pf_test_key');
    config()->set('services.pixelforge.usage_cache_seconds', 60);
    $this->travelTo('2026-11-18 14:03:00');
});

/**
 * A usage response as in docs/spec/pixelforge-api.md#usage, every day costing $cost.
 */
function pixelforgeUsage(Request $request, string $cost = '0.000000000'): array
{
    $days = [];

    foreach (CarbonPeriod::create($request['start_date'], $request['end_date']) as $day) {
        $days[] = [
            'date' => $day->toDateString(), 'generations' => 0, 'input_text_tokens' => 0,
            'input_image_tokens' => 0, 'output_tokens' => 0, 'reasoning_tokens' => 0, 'cost_usd' => $cost,
        ];
    }

    return ['object' => 'usage', 'start_date' => $request['start_date'], 'end_date' => $request['end_date'], 'data' => $days];
}

it('reads the provider\'s cost for every day of the range, exactly', function () {
    Http::fake(['pixelforge.test/v1/usage*' => fn (Request $request) => Http::response(pixelforgeUsage($request, '0.169080000'))]);

    $usage = app(PixelforgeImageProvider::class)->usage(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-02'));

    expect($usage->costOn('2026-11-01')?->nanodollars())->toBe(169_080_000)
        ->and($usage->costOn('2026-11-02')?->nanodollars())->toBe(169_080_000)
        ->and($usage->fetchedAt->toIso8601ZuluString())->toBe('2026-11-18T14:03:00Z');
    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer pf_test_key'));
});

it('asks for a range over 92 days in consecutive pieces of at most 92 days', function () {
    Http::fake(['pixelforge.test/v1/usage*' => fn (Request $request) => Http::response(pixelforgeUsage($request))]);

    $usage = app(PixelforgeImageProvider::class)->usage(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-07-19'));

    $pieces = Http::recorded()->map(fn (array $pair) => $pair[0]['start_date'].' '.$pair[0]['end_date'])->all();
    // 200 days: 92 + 92 + 16.
    expect($pieces)->toBe(['2026-01-01 2026-04-02', '2026-04-03 2026-07-03', '2026-07-04 2026-07-19'])
        ->and($usage->costOn('2026-07-19'))->not->toBeNull()
        ->and($usage->costOn('2026-01-01'))->not->toBeNull();
});

it('caches usage for the configured time', function () {
    Http::fake(['pixelforge.test/v1/usage*' => fn (Request $request) => Http::response(pixelforgeUsage($request))]);
    $provider = app(PixelforgeImageProvider::class);

    $provider->usage(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-02'));
    $this->travel(59)->seconds();
    $cached = $provider->usage(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-02'));
    $this->travel(2)->seconds();
    $provider->usage(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-02'));

    expect($cached->fetchedAt->toIso8601ZuluString())->toBe('2026-11-18T14:03:00Z');
    Http::assertSentCount(2);
});

it('throws our own exception when any usage request fails', function (Closure $fake) {
    $fake();

    app(PixelforgeImageProvider::class)->usage(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-07-19'));
})->with([
    'overloaded' => [fn () => Http::fake(['pixelforge.test/*' => Http::response(['error' => ['code' => 'overloaded']], 503)])],
    'network error' => [fn () => Http::fake(['pixelforge.test/*' => Http::failedConnection()])],
    'second piece fails' => [fn () => Http::fake(['pixelforge.test/*' => Http::sequence()
        ->push(['object' => 'usage', 'data' => []])
        ->push(['error' => ['code' => 'rate_limit_exceeded']], 429)])],
    'a day missing' => [fn () => Http::fake(['pixelforge.test/*' => Http::response(['object' => 'usage', 'data' => [['date' => '2026-01-01', 'cost_usd' => '0.000000000']]])])],
    'a malformed cost' => [fn () => Http::fake(['pixelforge.test/*' => Http::response(['object' => 'usage', 'data' => [['date' => '2026-01-01', 'cost_usd' => 0.1]]])])],
])->throws(UsageUnavailable::class);

it('defaults to caching usage for 1 minute', function () {
    $services = require config_path('services.php');

    expect($services['pixelforge']['usage_cache_seconds'])->toBe(60);
});

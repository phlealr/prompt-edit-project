<?php

use App\Enums\CreditTransactionType;
use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\ImageProviders\Exceptions\GenerationNotFound;
use App\ImageProviders\Exceptions\PromptRejected;
use App\ImageProviders\Exceptions\ProviderBusy;
use App\ImageProviders\Exceptions\ProviderUnreachable;
use App\ImageProviders\Rates;
use App\Jobs\Generations\PollGenerationsJob;
use App\Jobs\Generations\SubmitGenerationJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\ValueObjects\Money;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config()->set('services.pixelforge.poll_after_seconds', 120);
    config()->set('services.pixelforge.poll_max_per_run', 10);
});

/**
 * A generation accepted by the provider and quiet since $minutesAgo minutes.
 */
function quietGeneration(GenerationStatus $status, int $minutesAgo): Generation
{
    $generation = Generation::factory()->withHold()->create([
        'status' => $status,
        'provider_id' => 'gen_pf_'.fake()->unique()->numberBetween(1, 1_000_000),
    ]);
    $generation->forceFill(['created_at' => now()->subMinutes($minutesAgo), 'updated_at' => now()->subMinutes($minutesAgo)])->saveQuietly();

    return $generation;
}

function poll(): void
{
    PollGenerationsJob::dispatchSync();
}

it('is scheduled every minute', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->description, PollGenerationsJob::class));

    expect($event)->not->toBeNull()
        ->and($event?->expression)->toBe('* * * * *');
});

it('polls only queued and processing generations quiet for longer than the configured age', function () {
    $provider = fakeImageProvider();
    $queued = quietGeneration(GenerationStatus::Queued, 3);
    $processing = quietGeneration(GenerationStatus::Processing, 3);
    quietGeneration(GenerationStatus::Processing, 1); // updated a minute ago
    quietGeneration(GenerationStatus::Pending, 3);
    quietGeneration(GenerationStatus::Succeeded, 3);
    quietGeneration(GenerationStatus::Failed, 3);

    poll();

    expect($provider->fetched)->toEqualCanonicalizing([$queued->provider_id, $processing->provider_id]);
});

it('polls at most the configured number per run, least recently updated or polled first', function () {
    $provider = fakeImageProvider();
    config()->set('services.pixelforge.poll_max_per_run', 2);
    $oldest = quietGeneration(GenerationStatus::Processing, 30);
    $older = quietGeneration(GenerationStatus::Processing, 20);
    quietGeneration(GenerationStatus::Processing, 10);

    poll();

    expect($provider->fetched)->toBe([$oldest->provider_id, $older->provider_id]);
});

it('does not pick a generation polled with no change before others that waited longer', function () {
    $provider = fakeImageProvider();
    config()->set('services.pixelforge.poll_max_per_run', 1);
    $stuck = quietGeneration(GenerationStatus::Processing, 30);
    $other = quietGeneration(GenerationStatus::Processing, 20);

    poll();
    $this->travel(3)->minutes();
    poll();

    expect($provider->fetched)->toBe([$stuck->provider_id, $other->provider_id])
        ->and($stuck->fresh()?->polled_at)->not->toBeNull();
});

it('reaches the provider\'s final status by polling, with a release and a charge as by webhook', function () {
    config()->set('services.pixelforge.base_url', 'https://pixelforge.test/v1');
    $generation = Generation::factory()->withHold()->create([
        'status' => GenerationStatus::Processing,
        'provider_id' => 'gen_pf_1',
        'estimate_maximum' => Money::fromNanodollars(467_343_750),
    ]);
    $generation->forceFill(['updated_at' => now()->subMinutes(3)])->saveQuietly();
    Http::fake(['pixelforge.test/v1/generations/gen_pf_1' => Http::response(pixelforgeGeneration([
        'id' => 'gen_pf_1',
        'status' => 'succeeded',
        'usage' => ['input_text_tokens' => 115, 'input_image_tokens' => 1530, 'output_tokens' => 6450, 'reasoning_tokens' => 8000, 'total_tokens' => 16095],
        'completed_at' => '2026-11-04T14:03:29Z',
    ]))]);

    poll();

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Succeeded)
        ->and($generation->fresh()?->provider_cost?->nanodollars())->toBe(373_875_000)
        ->and(CreditTransaction::query()->where('generation_id', $generation->id)->orderBy('id')->pluck('type')->all())
        ->toBe([CreditTransactionType::Hold, CreditTransactionType::Release, CreditTransactionType::Charge]);
});

it('ends the run without error on a 429', function () {
    $provider = fakeImageProvider();
    $first = quietGeneration(GenerationStatus::Processing, 30);
    quietGeneration(GenerationStatus::Processing, 20);
    $provider->fetchFailures = [new ProviderBusy(5, 'Pixelforge answered 429 rate_limit_exceeded.')];

    poll();

    expect($provider->fetched)->toBe([$first->provider_id]);
});

it('skips a generation on a 503 or network error, and carries on with the others', function () {
    $provider = fakeImageProvider();
    $first = quietGeneration(GenerationStatus::Processing, 30);
    $second = quietGeneration(GenerationStatus::Processing, 20);
    $provider->fetchFailures = [new ProviderUnreachable('Connection timed out.')];

    poll();

    expect($provider->fetched)->toBe([$first->provider_id, $second->provider_id])
        ->and($first->fresh()?->status)->toBe(GenerationStatus::Processing);
});

it('logs an error and changes nothing when the provider answers 404', function () {
    Log::spy();
    $provider = fakeImageProvider();
    $generation = quietGeneration(GenerationStatus::Processing, 30);
    $provider->fetchFailures = [new GenerationNotFound('Pixelforge answered 404 not_found.')];

    poll();

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Processing)
        ->and(CreditTransaction::query()->where('generation_id', $generation->id)->count())->toBe(1);
    Log::shouldHaveReceived('error')->once();
});

it('logs an error and keeps the hold for a generation still not final an hour after submission', function () {
    Log::spy();
    $provider = fakeImageProvider(); // fetch answers processing
    $generation = quietGeneration(GenerationStatus::Processing, 61);

    poll();

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Processing)
        ->and(CreditTransaction::query()->where('generation_id', $generation->id)->count())->toBe(1);
    Log::shouldHaveReceived('error')->once();
});

it('re-sends a pending generation that may exist at the provider once its submission job gave up', function () {
    $provider = fakeImageProvider(); // submit answers queued
    $generation = Generation::factory()->withHold()->create(['may_exist_at_provider' => true]);
    $provider->currentRates = new Rates(
        Money::fromDecimalString('5.00'), Money::fromDecimalString('10.00'), Money::fromDecimalString('99.00'), Money::fromDecimalString('12.50'),
    );

    (new SubmitGenerationJob($generation->id))->failed(new MaxAttemptsExceededException('Out of attempts.'));
    poll();

    expect($provider->submitted)->toBe([$generation->uuid])
        ->and($provider->currentRatesCalls)->toBe(0)
        ->and($generation->fresh()?->status)->toBe(GenerationStatus::Queued);
});

it('leaves a pending generation alone while its submission job is still trying', function () {
    $provider = fakeImageProvider();
    Generation::factory()->withHold()->create(['may_exist_at_provider' => true]);

    poll();

    expect($provider->submitted)->toBe([]);
});

it('skips a generation polled within the configured age, even if it changed long ago', function () {
    $provider = fakeImageProvider();
    $generation = quietGeneration(GenerationStatus::Processing, 30);
    Generation::query()->whereKey($generation->id)->toBase()->update(['polled_at' => now()->subMinute()]);

    poll();

    expect($provider->fetched)->toBe([]);
});

it('fails a re-sent generation the provider refuses, through the one action, and carries on', function () {
    $provider = fakeImageProvider();
    $resent = Generation::factory()->withHold()->create(['may_exist_at_provider' => true, 'submission_gave_up_at' => now()]);
    $other = quietGeneration(GenerationStatus::Processing, 30);
    $provider->submitFailures = [new PromptRejected('Pixelforge answered 400 content_policy_violation.')];

    poll();

    expect($resent->fresh()?->status)->toBe(GenerationStatus::Failed)
        ->and($resent->fresh()?->failure_reason)->toBe(FailureReason::PromptRejected)
        ->and(CreditTransaction::query()->where('generation_id', $resent->id)->count())->toBe(2)
        ->and($provider->fetched)->toBe([$other->provider_id]);
});

it('logs an unexpected error for one generation and carries on with the others', function () {
    Log::spy();
    $provider = fakeImageProvider();
    $broken = quietGeneration(GenerationStatus::Processing, 30);
    $other = quietGeneration(GenerationStatus::Processing, 20);
    $provider->fetchFailures = [new RuntimeException('Something unexpected.')];

    poll();

    expect($provider->fetched)->toBe([$broken->provider_id, $other->provider_id])
        ->and($broken->fresh()?->polled_at)->not->toBeNull();
    Log::shouldHaveReceived('error')->once();
});

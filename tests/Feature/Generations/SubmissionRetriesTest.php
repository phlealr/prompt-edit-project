<?php

use App\Enums\CreditTransactionType;
use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\ImageProviders\Exceptions\ProviderBusy;
use App\ImageProviders\Exceptions\ProviderUnreachable;
use App\ImageProviders\Exceptions\SubmissionRefused;
use App\ImageProviders\Rates;
use App\Jobs\Generations\SubmitGenerationJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\ValueObjects\Money;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->provider = fakeImageProvider();
    config()->set('services.pixelforge.submit_max_attempts', 8);
    config()->set('services.pixelforge.submit_backoff_cap_seconds', 60);
});

/**
 * Runs the submission job once, as its given attempt, without a real queue.
 */
function runSubmissionAttempt(Generation $generation, int $attempt): SubmitGenerationJob
{
    $job = (new SubmitGenerationJob($generation->id))->withFakeQueueInteractions();
    $job->job->attempts = $attempt;
    app()->call([$job, 'handle']);

    return $job;
}

it('waits the longer of Retry-After and the attempt\'s backoff, keeping the generation pending', function (int $attempt, int $retryAfter, int $delay) {
    $generation = Generation::factory()->withHold()->create();
    $this->provider->submitFailures = [new ProviderBusy($retryAfter, 'Pixelforge answered 503 overloaded.')];

    runSubmissionAttempt($generation, $attempt)->assertReleased(delay: $delay);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Pending);
})->with([
    'attempt 1: backoff 5 s beats Retry-After 2 s' => [1, 2, 5],
    'attempt 1: Retry-After 17 s beats backoff 5 s' => [1, 17, 17],
    'attempt 3: backoff 20 s' => [3, 5, 20],
    'attempt 5: backoff 80 s capped at 60 s' => [5, 2, 60],
]);

it('retries a network error after the attempt\'s backoff', function () {
    $generation = Generation::factory()->withHold()->create();
    $this->provider->submitFailures = [new ProviderUnreachable('Connection timed out.')];

    runSubmissionAttempt($generation, 2)->assertReleased(delay: 10);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Pending);
});

it('leaves one generation and one hold when a retry is then accepted', function () {
    $generation = Generation::factory()->withHold()->create();
    $this->provider->submitFailures = [new ProviderBusy(2, 'Pixelforge answered 429 rate_limit_exceeded.')];

    runSubmissionAttempt($generation, 1)->assertReleased();
    runSubmissionAttempt($generation, 2)->assertNotReleased();

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Queued)
        ->and($this->provider->submitted)->toBe([$generation->uuid, $generation->uuid])
        ->and(Generation::query()->count())->toBe(1)
        ->and(CreditTransaction::query()->where('generation_id', $generation->id)->pluck('type')->all())
        ->toBe([CreditTransactionType::Hold]);
});

it('sends the attempts after a network error without the rate check', function () {
    $generation = Generation::factory()->withHold()->create();
    $this->provider->submitFailures = [
        new ProviderUnreachable('Connection timed out.'),
        new ProviderBusy(2, 'Pixelforge answered 503 overloaded.'),
    ];

    runSubmissionAttempt($generation, 1);
    $this->provider->currentRates = new Rates(
        Money::fromDecimalString('5.00'),
        Money::fromDecimalString('10.00'),
        Money::fromDecimalString('41.00'),
        Money::fromDecimalString('12.50'),
    );
    runSubmissionAttempt($generation, 2);
    runSubmissionAttempt($generation, 3);

    // Only the first attempt checked rates; the others were sent although the rates changed.
    expect($this->provider->currentRatesCalls)->toBe(1)
        ->and($this->provider->submitted)->toHaveCount(3)
        ->and($generation->fresh()?->status)->toBe(GenerationStatus::Queued);
});

it('fails the generation with provider_unavailable and releases its hold once the attempts run out', function () {
    $generation = Generation::factory()->withHold()->create();

    (new SubmitGenerationJob($generation->id))->failed(new MaxAttemptsExceededException('Out of attempts.'));

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Failed)
        ->and($generation->fresh()?->failure_reason)->toBe(FailureReason::ProviderUnavailable)
        ->and(CreditTransaction::query()->where('generation_id', $generation->id)->where('type', CreditTransactionType::Release)->sole()->amount->nanodollars())
        ->toBe($generation->estimate_maximum->nanodollars());
});

it('takes the maximum number of attempts and the backoff cap from configuration', function () {
    config()->set('services.pixelforge.submit_max_attempts', 3);
    config()->set('services.pixelforge.submit_backoff_cap_seconds', 15);
    $generation = Generation::factory()->withHold()->create();
    $this->provider->submitFailures = [new ProviderBusy(1, 'Pixelforge answered 503 overloaded.')];

    expect((new SubmitGenerationJob($generation->id))->tries())->toBe(3);
    runSubmissionAttempt($generation, 3)->assertReleased(delay: 15);
});

it('defaults to 8 attempts and a 60 second backoff cap', function () {
    $services = require config_path('services.php');

    expect($services['pixelforge']['submit_max_attempts'])->toBe(8)
        ->and($services['pixelforge']['submit_backoff_cap_seconds'])->toBe(60);
});

it('retries after the attempt\'s backoff when pricing is unavailable for the rate check', function () {
    $generation = Generation::factory()->withHold()->create();
    $this->provider->pricingUnavailable = true;

    runSubmissionAttempt($generation, 2)->assertReleased(delay: 10);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Pending)
        ->and($this->provider->submitted)->toBe([]);
});

it('leaves a generation that may exist at the provider pending with its hold when the attempts run out', function () {
    Log::spy();
    $generation = Generation::factory()->withHold()->create(['may_exist_at_provider' => true]);

    (new SubmitGenerationJob($generation->id))->failed(new MaxAttemptsExceededException('Out of attempts.'));

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Pending)
        ->and(CreditTransaction::query()->where('generation_id', $generation->id)->pluck('type')->all())
        ->toBe([CreditTransactionType::Hold]);
    Log::shouldHaveReceived('error')->once();
});

it('keeps a generation that may exist at the provider when a later attempt is refused, and retries it', function () {
    $generation = Generation::factory()->withHold()->create(['may_exist_at_provider' => true]);
    $this->provider->submitFailures = [new SubmissionRefused('Pixelforge answered 401 invalid_api_key.')];

    runSubmissionAttempt($generation, 3)->assertReleased(delay: 20);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Pending)
        ->and(CreditTransaction::query()->where('generation_id', $generation->id)->count())->toBe(1);
});

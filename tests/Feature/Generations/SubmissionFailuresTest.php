<?php

use App\Actions\Credits\CalculateBalance;
use App\Actions\Generations\ApplyProviderUpdate;
use App\Actions\Generations\SubmitGeneration;
use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Exceptions\PromptRejected;
use App\ImageProviders\Exceptions\SubmissionRefused;
use App\ImageProviders\ProviderUpdate;
use App\ImageProviders\Rates;
use App\Jobs\Generations\SubmitGenerationJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->provider = fakeImageProvider();
});

/**
 * Submits a generation the way the generate form does, with the job held back, for a user who
 * bought $1. Returns the generation, still pending, with its hold.
 */
function submittedGeneration(): Generation
{
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(1_000_000_000)->create();

    // The queue is faked only while submitting, so the test runs the job itself.
    return Queue::fakeFor(fn (): Generation => app(SubmitGeneration::class)
        ->handle($user, 'A cat [blocked]', Size::Square, Quality::Low, []));
}

/**
 * The generation's ledger rows, oldest first, as "type nanodollars".
 *
 * @return list<string>
 */
function ledgerOf(Generation $generation): array
{
    return CreditTransaction::query()
        ->where('generation_id', $generation->id)
        ->orderBy('id')
        ->get()
        ->map(fn (CreditTransaction $row): string => "{$row->type->value} {$row->amount->nanodollars()}")
        ->all();
}

it('fails a generation whose rates changed with price_changed, sends nothing, and releases its hold', function () {
    $generation = submittedGeneration(); // hold: −20,000,000
    $this->provider->currentRates = new Rates(
        Money::fromDecimalString('5.00'),
        Money::fromDecimalString('10.00'),
        Money::fromDecimalString('41.00'),
        Money::fromDecimalString('12.50'),
    );

    SubmitGenerationJob::dispatchSync($generation->id);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Failed)
        ->and($generation->fresh()?->failure_reason)->toBe(FailureReason::PriceChanged)
        ->and($this->provider->submitted)->toBe([])
        ->and(ledgerOf($generation))->toBe(['hold -20000000', 'release 20000000']);
});

it('fails a rejected prompt with prompt_rejected after one attempt, and gives the balance back', function () {
    $generation = submittedGeneration();
    $user = $generation->user;
    $this->provider->submitThrows = new PromptRejected('The prompt was rejected.');

    SubmitGenerationJob::dispatchSync($generation->id);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Failed)
        ->and($generation->fresh()?->failure_reason)->toBe(FailureReason::PromptRejected)
        ->and($this->provider->submitted)->toHaveCount(1)
        ->and(ledgerOf($generation))->toBe(['hold -20000000', 'release 20000000'])
        ->and(app(CalculateBalance::class)->handle($user)->nanodollars())->toBe(1_000_000_000);
});

it('fails a refused submission with provider_unavailable after one attempt, releases its hold and logs an error', function () {
    Log::spy();
    $generation = submittedGeneration();
    $this->provider->submitThrows = new SubmissionRefused('Pixelforge refused the submission: 429 daily_limit_exceeded.');

    SubmitGenerationJob::dispatchSync($generation->id);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Failed)
        ->and($generation->fresh()?->failure_reason)->toBe(FailureReason::ProviderUnavailable)
        ->and($this->provider->submitted)->toHaveCount(1)
        ->and(ledgerOf($generation))->toBe(['hold -20000000', 'release 20000000']);
    Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => str_contains($context['answer'], 'daily_limit_exceeded'))->once();
});

it('releases the hold once when the same failure is applied twice', function () {
    $generation = submittedGeneration();
    $failure = ProviderUpdate::failedBeforeCreation(FailureReason::PromptRejected);

    app(ApplyProviderUpdate::class)->handle($generation, $failure);
    app(ApplyProviderUpdate::class)->handle($generation, $failure);

    expect(ledgerOf($generation))->toBe(['hold -20000000', 'release 20000000']);
});

it('sets failure_reason exactly when the status is failed', function () {
    $generation = submittedGeneration();

    expect($generation->fresh()?->failure_reason)->toBeNull();

    SubmitGenerationJob::dispatchSync($generation->id);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Queued)
        ->and($generation->fresh()?->failure_reason)->toBeNull();
});

it('gives each failure reason its message from the spec', function (FailureReason $reason, string $message) {
    expect($reason->message())->toBe($message);
})->with([
    [FailureReason::PromptRejected, "Your prompt was rejected by the image provider's content policy. You were not charged."],
    [FailureReason::ProviderFailed, 'The image could not be generated. You were not charged. Please try again.'],
    [FailureReason::ProviderUnavailable, 'The image service was busy or unavailable, so we gave up. You were not charged. Please try again.'],
    [FailureReason::PriceChanged, "Prices changed after your estimate, so this generation wasn't submitted. You were not charged. Check the new estimate and submit again."],
]);

it('never fails a generation the provider already created with a failure decided before sending', function () {
    $generation = Generation::factory()->withHold()->create(['status' => GenerationStatus::Queued, 'provider_id' => 'gen_1']);

    app(ApplyProviderUpdate::class)->handle($generation, ProviderUpdate::failedBeforeCreation(FailureReason::PriceChanged));

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Queued)
        ->and(ledgerOf($generation))->toHaveCount(1);
});

it('refuses a provider update whose failure reason does not match its status', function (GenerationStatus $status, ?FailureReason $reason) {
    new ProviderUpdate('gen_1', $status, null, $reason);
})->with([
    'failed without a reason' => [GenerationStatus::Failed, null],
    'a reason without failed' => [GenerationStatus::Queued, FailureReason::ProviderFailed],
])->throws(InvalidArgumentException::class);

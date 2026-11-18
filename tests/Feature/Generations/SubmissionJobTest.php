<?php

use App\Actions\Generations\ApplyProviderUpdate;
use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\ImageProviders\ProviderUpdate;
use App\ImageProviders\Rates;
use App\Jobs\Generations\SubmitGenerationJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->provider = fakeImageProvider();
});

it('moves an accepted generation to queued and stores the provider\'s id', function () {
    $generation = Generation::factory()->create();
    $this->provider->submitStatus = GenerationStatus::Queued;

    SubmitGenerationJob::dispatchSync($generation->id);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Queued)
        ->and($generation->fresh()?->provider_id)->toBe("gen_fake_{$generation->id}")
        ->and($this->provider->submitted)->toBe([$generation->uuid]);
});

it('applies a create response with a later status, from an idempotent replay', function () {
    $generation = Generation::factory()->create();
    $this->provider->submitStatus = GenerationStatus::Processing;

    SubmitGenerationJob::dispatchSync($generation->id);

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Processing);
});

it('never moves a generation back', function (GenerationStatus $current, GenerationStatus $update) {
    $generation = Generation::factory()->create(['status' => $current, 'provider_id' => 'gen_1']);

    app(ApplyProviderUpdate::class)->handle($generation, new ProviderUpdate('gen_1', $update, '{}'));

    expect($generation->fresh()?->status)->toBe($current);
})->with([
    'processing, then queued' => [GenerationStatus::Processing, GenerationStatus::Queued],
    'queued, then queued again' => [GenerationStatus::Queued, GenerationStatus::Queued],
    'succeeded, then processing' => [GenerationStatus::Succeeded, GenerationStatus::Processing],
    'failed, then succeeded' => [GenerationStatus::Failed, GenerationStatus::Succeeded],
]);

it('leaves a final provider status for the tickets that settle the hold', function (GenerationStatus $final) {
    $generation = Generation::factory()->create();

    app(ApplyProviderUpdate::class)->handle($generation, new ProviderUpdate('gen_1', $final, '{}'));

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Pending);
})->with([GenerationStatus::Succeeded, GenerationStatus::Failed]);

it('creates no second provider generation, hold or release when the job runs twice', function () {
    $generation = Generation::factory()->create();
    CreditTransaction::factory()->for($generation->user)->create([
        'type' => CreditTransactionType::Hold,
        'generation_id' => $generation->id,
        'amount' => $generation->estimate_maximum->negate(),
    ]);

    SubmitGenerationJob::dispatchSync($generation->id);
    SubmitGenerationJob::dispatchSync($generation->id);

    expect($this->provider->submitted)->toBe([$generation->uuid])
        ->and(CreditTransaction::query()->where('generation_id', $generation->id)->pluck('type')->all())
        ->toBe([CreditTransactionType::Hold]);
});

it('checks fresh rates before sending, and sends only when they equal the stored ones', function () {
    $generation = Generation::factory()->create(); // stored output rate: $40.00
    $this->provider->currentRates = new Rates(
        Money::fromDecimalString('5.00'),
        Money::fromDecimalString('10.00'),
        Money::fromDecimalString('41.00'),
        Money::fromDecimalString('12.50'),
    );

    SubmitGenerationJob::dispatchSync($generation->id);

    expect($this->provider->submitted)->toBe([])
        ->and($this->provider->currentRatesCalls)->toBe(1);
});

it('compares rates by amount, not by how they are written', function () {
    $generation = Generation::factory()->create(); // stored output rate: $40.00
    $this->provider->currentRates = new Rates(
        Money::fromDecimalString('5'),
        Money::fromDecimalString('10.0'),
        Money::fromDecimalString('40.000'),
        Money::fromDecimalString('12.5'),
    );

    SubmitGenerationJob::dispatchSync($generation->id);

    expect($this->provider->submitted)->toBe([$generation->uuid]);
});

it('never calls the provider to create a generation from the web request', function () {
    Queue::fake();
    $user = Generation::factory()->create()->user;
    CreditTransaction::factory()->for($user)->purchase(1_000_000_000)->create();

    $this->actingAs($user)->post(route('generations.store'), [
        'prompt' => 'A cat', 'size' => '1024x1024', 'quality' => 'medium',
    ])->assertRedirect(route('history'));

    expect($this->provider->submitted)->toBe([]);
    Queue::assertPushed(SubmitGenerationJob::class);
});

<?php

use App\Actions\Credits\CalculateBalance;
use App\Actions\Generations\ApplyProviderUpdate;
use App\Enums\CreditTransactionType;
use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\ImageProviders\ProviderUpdate;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config()->set('services.pixelforge.webhook_secret', 'whsec_test');
});

/**
 * Pixelforge posting an event about our generation, signed; the sync queue applies it at once.
 *
 * @param  array<string, mixed>  $generation  Fields of the generation object to override.
 */
function deliverEvent(string $eventId, string $type, array $generation): TestResponse
{
    $body = pixelforgeEvent($eventId, $type, $generation);

    return test()->call('POST', '/webhooks/pixelforge', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PIXELFORGE_SIGNATURE' => pixelforgeSignature($body, now()->timestamp),
    ], content: $body);
}

/**
 * A generation of a user who bought $1, submitted with its hold (−$0.08625).
 */
function generationWithHold(GenerationStatus $status, ?string $providerId = 'gen_pf_1'): Generation
{
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(1_000_000_000)->create();

    return Generation::factory()->for($user)->withHold()->create(['status' => $status, 'provider_id' => $providerId]);
}

function ledgerTypes(Generation $generation): array
{
    return CreditTransaction::query()->where('generation_id', $generation->id)->orderBy('id')->pluck('type')->all();
}

it('moves a queued or pending generation to processing', function (GenerationStatus $from) {
    $generation = generationWithHold($from, $from === GenerationStatus::Pending ? null : 'gen_pf_1');

    deliverEvent('evt_1', 'generation.processing', [
        'id' => 'gen_pf_1', 'status' => 'processing', 'metadata' => ['generation_id' => (string) $generation->id],
    ])->assertOk();

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Processing)
        ->and($generation->fresh()?->provider_id)->toBe('gen_pf_1');
})->with([GenerationStatus::Queued, GenerationStatus::Pending]);

it('[fail]: fails with provider_failed, keeps the provider\'s code and message, and gives the balance back', function (string $code) {
    $generation = generationWithHold(GenerationStatus::Processing);
    $user = $generation->user;

    deliverEvent('evt_2', 'generation.failed', [
        'id' => 'gen_pf_1',
        'status' => 'failed',
        'metadata' => ['generation_id' => (string) $generation->id],
        'error' => ['code' => $code, 'message' => 'Something went wrong while generating.'],
    ])->assertOk();

    $failed = $generation->fresh();
    expect($failed?->status)->toBe(GenerationStatus::Failed)
        ->and($failed?->failure_reason)->toBe(FailureReason::ProviderFailed)
        ->and($failed?->provider_response)->toContain($code)->toContain('Something went wrong while generating.')
        ->and(ledgerTypes($generation))->toBe([CreditTransactionType::Hold, CreditTransactionType::Release])
        ->and(app(CalculateBalance::class)->handle($user)->nanodollars())->toBe(1_000_000_000);
})->with(['generation_failed', 'internal_error']);

it('never resubmits a generation the provider failed', function (string $code) {
    $provider = fakeImageProvider();
    $generation = generationWithHold(GenerationStatus::Processing);

    deliverEvent('evt_2', 'generation.failed', [
        'id' => 'gen_pf_1', 'status' => 'failed', 'metadata' => ['generation_id' => (string) $generation->id],
        'error' => ['code' => $code, 'message' => 'Please retry.'],
    ]);

    expect($provider->submitted)->toBe([]);
})->with(['generation_failed', 'internal_error']);

it('changes nothing when processing arrives after a final status', function (GenerationStatus $final) {
    $generation = generationWithHold($final);

    deliverEvent('evt_3', 'generation.processing', [
        'id' => 'gen_pf_1', 'status' => 'processing', 'metadata' => ['generation_id' => (string) $generation->id],
    ])->assertOk();

    expect($generation->fresh()?->status)->toBe($final)
        ->and(ledgerTypes($generation))->toBe([CreditTransactionType::Hold]);
})->with([GenerationStatus::Succeeded, GenerationStatus::Failed]);

it('moves a pending generation forward from a webhook, and the submission response then changes nothing', function () {
    $generation = generationWithHold(GenerationStatus::Pending, null);

    deliverEvent('evt_4', 'generation.processing', [
        'id' => 'gen_pf_1', 'status' => 'processing', 'metadata' => ['generation_id' => (string) $generation->id],
    ]);
    // The create request was already in flight; its "queued" answer arrives after the webhook.
    app(ApplyProviderUpdate::class)->handle($generation, new ProviderUpdate('gen_pf_1', GenerationStatus::Queued, '{}'));

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Processing)
        ->and($generation->fresh()?->provider_id)->toBe('gen_pf_1');
});

it('ignores an event about another provider generation than the one stored, and logs a warning', function () {
    Log::spy();
    $generation = generationWithHold(GenerationStatus::Processing, 'gen_pf_1');

    deliverEvent('evt_6', 'generation.failed', [
        'id' => 'gen_pf_other', 'status' => 'failed', 'metadata' => ['generation_id' => (string) $generation->id],
        'error' => ['code' => 'internal_error', 'message' => 'Please retry.'],
    ])->assertOk();

    expect($generation->fresh()?->status)->toBe(GenerationStatus::Processing)
        ->and($generation->fresh()?->provider_id)->toBe('gen_pf_1')
        ->and(ledgerTypes($generation))->toBe([CreditTransactionType::Hold]);
    Log::shouldHaveReceived('warning')->once();
});

it('records an event it cannot apply, answers 200, applies nothing and logs a warning', function (array $metadata) {
    Log::spy();
    $generation = generationWithHold(GenerationStatus::Processing);

    deliverEvent('evt_5', 'generation.failed', [
        'id' => 'gen_pf_9', 'status' => 'failed', 'metadata' => $metadata,
        'error' => ['code' => 'internal_error', 'message' => 'Please retry.'],
    ])->assertOk();

    expect(WebhookEvent::query()->where('provider_event_id', 'evt_5')->exists())->toBeTrue()
        ->and($generation->fresh()?->status)->toBe(GenerationStatus::Processing)
        ->and(ledgerTypes($generation))->toBe([CreditTransactionType::Hold]);
    Log::shouldHaveReceived('warning')->once();
})->with([
    'unknown generation' => [['generation_id' => '999999']],
    'no generation id' => [[]],
    'an id that is not a whole number' => [['generation_id' => '1e2']],
]);

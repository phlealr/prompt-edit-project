<?php

use App\Actions\Credits\CalculateBalance;
use App\Actions\Generations\SubmitGeneration;
use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\Exceptions\NotEnoughCredits;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config()->set('services.pixelforge.webhook_secret', 'whsec_test');
});

/**
 * The worked example's usage (docs/spec/pixelforge-api.md#worked-example-the-most-a-generation-can-cost),
 * succeeded, about our generation.
 *
 * @param  array<string, string>  $rates
 */
function succeededEvent(Generation $generation, string $eventId = 'evt_ok', array $rates = []): TestResponse
{
    $body = pixelforgeEvent($eventId, 'generation.succeeded', [
        'id' => 'gen_pf_1',
        'status' => 'succeeded',
        'metadata' => ['generation_id' => (string) $generation->id],
        'image_url' => 'https://pixelforge.promptedit.dev/images/gen_pf_1.png?expires=1762351409&signature=abc',
        'image_url_expires_at' => '2026-11-05T14:03:29Z',
        'usage' => [
            'input_text_tokens' => 115,
            'input_image_tokens' => 1530,
            'output_tokens' => 6450,
            'reasoning_tokens' => 8000,
            'total_tokens' => 16095,
        ],
        'rates' => [
            'input_text_tokens' => '5.00',
            'input_image_tokens' => '10.00',
            'output_tokens' => '40.00',
            'reasoning_tokens' => '12.50',
            ...$rates,
        ],
        'started_at' => '2026-11-04T14:03:13Z',
        'completed_at' => '2026-11-04T14:03:29Z',
    ]);

    return test()->call('POST', '/webhooks/pixelforge', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PIXELFORGE_SIGNATURE' => pixelforgeSignature($body, now()->timestamp),
    ], content: $body);
}

/**
 * A processing generation of a user who bought $1, holding the worked example's maximum.
 */
function processingWorkedExample(): Generation
{
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(1_000_000_000)->create();

    return Generation::factory()->for($user)->withHold()->create([
        'status' => GenerationStatus::Processing,
        'provider_id' => 'gen_pf_1',
        'estimate_maximum' => Money::fromNanodollars(467_343_750),
    ]);
}

/**
 * @return list<string>
 */
function ledgerRowsOf(Generation $generation): array
{
    return CreditTransaction::query()->where('generation_id', $generation->id)->orderBy('id')->get()
        ->map(fn (CreditTransaction $row): string => "{$row->type->value} {$row->amount->nanodollars()}")
        ->all();
}

it('records a succeeded generation: usage, rates, provider cost, charge, image and the provider\'s times', function () {
    $generation = processingWorkedExample();

    succeededEvent($generation)->assertOk();

    $succeeded = $generation->fresh();
    expect($succeeded?->status)->toBe(GenerationStatus::Succeeded)
        ->and($succeeded?->provider_cost?->nanodollars())->toBe(373_875_000)
        ->and($succeeded?->image_url)->toBe('https://pixelforge.promptedit.dev/images/gen_pf_1.png?expires=1762351409&signature=abc')
        ->and($succeeded?->image_url_expires_at?->toIso8601ZuluString())->toBe('2026-11-05T14:03:29Z')
        ->and($succeeded?->started_at?->toIso8601ZuluString())->toBe('2026-11-04T14:03:13Z')
        ->and($succeeded?->completed_at?->toIso8601ZuluString())->toBe('2026-11-04T14:03:29Z')
        ->and($succeeded?->provider_response)->toContain('"output_tokens":6450')->toContain('"reasoning_tokens":"12.50"');
});

it('charges the worked example $0.467343750 for a provider cost of $0.373875000, and releases the hold', function () {
    $generation = processingWorkedExample();

    succeededEvent($generation);

    expect(ledgerRowsOf($generation))->toBe([
        'hold -467343750',
        'release 467343750',
        'charge -467343750',
    ]);
});

it('computes the provider cost from the rates on the provider\'s generation, not current pricing', function () {
    $generation = processingWorkedExample(); // stored rates: output $40.00
    config()->set('services.pixelforge.base_url', 'https://pixelforge.test/v1');
    Http::fake(['pixelforge.test/v1/pricing' => Http::response(pixelforgePricing(['output_tokens' => '20.00']))]);

    // Output at $80.00: 0.000575 + 0.0153 + 0.516 + 0.1 = $0.631875000; charge $0.789843750.
    succeededEvent($generation, rates: ['output_tokens' => '80.00']);

    expect($generation->fresh()?->provider_cost?->nanodollars())->toBe(631_875_000)
        ->and(ledgerRowsOf($generation)[2])->toBe('charge -789843750');
    Http::assertNothingSent();
});

it('leaves one release and one charge when the same success is applied twice', function () {
    $generation = processingWorkedExample();

    succeededEvent($generation, 'evt_ok_1');
    succeededEvent($generation, 'evt_ok_2');

    expect(ledgerRowsOf($generation))->toHaveCount(3);
});

it('charges in full above the hold, logs a warning, and lets the balance go below zero', function () {
    Log::spy();
    $user = User::factory()->create(); // no purchase: the hold alone takes the balance down
    $generation = Generation::factory()->for($user)->withHold()->create([
        'status' => GenerationStatus::Processing,
        'provider_id' => 'gen_pf_1',
        'estimate_maximum' => Money::fromNanodollars(400_000_000),
    ]);

    succeededEvent($generation);

    expect(ledgerRowsOf($generation))->toBe(['hold -400000000', 'release 400000000', 'charge -467343750'])
        ->and(app(CalculateBalance::class)->handle($user)->nanodollars())->toBe(-467_343_750);
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context) => $context === [
        'generation' => $generation->id,
        'hold' => '$0.400000000',
        'charge' => '$0.467343750',
        'difference' => '$0.067343750',
    ])->once();
});

it('refuses a submission from a user whose balance is below zero', function () {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->withHold()->create([
        'status' => GenerationStatus::Processing,
        'provider_id' => 'gen_pf_1',
        'estimate_maximum' => Money::fromNanodollars(400_000_000),
    ]);
    succeededEvent($generation); // balance: −$0.467343750
    fakeImageProvider();

    app(SubmitGeneration::class)->handle($user, 'A cat', Size::Square, Quality::Low, []);
})->throws(NotEnoughCredits::class);

it('lets uncovered charges and negative balances be listed from the ledger alone', function () {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->withHold()->create([
        'status' => GenerationStatus::Processing,
        'provider_id' => 'gen_pf_1',
        'estimate_maximum' => Money::fromNanodollars(400_000_000),
    ]);
    succeededEvent($generation);

    $uncovered = DB::table('credit_transactions as charges')
        ->join('credit_transactions as holds', fn ($join) => $join
            ->on('holds.generation_id', '=', 'charges.generation_id')
            ->where('holds.type', CreditTransactionType::Hold->value))
        ->where('charges.type', CreditTransactionType::Charge->value)
        ->whereColumn('charges.amount', '<', 'holds.amount')
        ->pluck('charges.generation_id')
        ->all();
    $negative = DB::table('credit_transactions')
        ->groupBy('user_id')
        ->havingRaw('sum(amount) < 0')
        ->pluck('user_id')
        ->all();

    expect($uncovered)->toBe([$generation->id])
        ->and($negative)->toBe([$user->id]);
});

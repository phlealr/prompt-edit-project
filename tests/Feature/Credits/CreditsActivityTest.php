<?php

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A ledger row for this user, at a given time.
 */
function ledgerRow(User $user, CreditTransactionType $type, int $nanodollars, ?Generation $generation = null, string $at = '2026-11-18 10:00:00'): void
{
    $row = CreditTransaction::factory()->for($user)->create([
        'type' => $type,
        'amount' => Money::fromNanodollars($nanodollars),
        'generation_id' => $generation?->id,
    ]);
    CreditTransaction::query()->whereKey($row->id)->toBase()->update(['created_at' => $at]);
}

it('lists purchases and charges only, newest first, with their signed amount, time, and the charge\'s prompt', function () {
    $user = User::factory()->create();
    $succeeded = Generation::factory()->for($user)->create(['status' => GenerationStatus::Succeeded, 'prompt' => 'A lighthouse at dawn']);
    $failed = Generation::factory()->for($user)->create(['status' => GenerationStatus::Failed]);
    ledgerRow($user, CreditTransactionType::Purchase, 10_000_000_000, at: '2026-11-18 09:00:00');
    ledgerRow($user, CreditTransactionType::Hold, -467_343_750, $succeeded, '2026-11-18 09:10:00');
    ledgerRow($user, CreditTransactionType::Release, 467_343_750, $succeeded, '2026-11-18 09:11:00');
    ledgerRow($user, CreditTransactionType::Charge, -34_500_000, $succeeded, '2026-11-18 09:11:00');
    ledgerRow($user, CreditTransactionType::Hold, -86_250_000, $failed, '2026-11-18 09:20:00');
    ledgerRow($user, CreditTransactionType::Release, 86_250_000, $failed, '2026-11-18 09:21:00');

    $this->actingAs($user)->get(route('credits'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('summary.activity.data', 2)
            ->where('summary.activity.data.0', [
                'kind' => 'charge',
                'amount' => ['user' => '-$0.0345', 'exact' => '-$0.034500000'],
                'prompt' => 'A lighthouse at dawn',
                'createdAt' => '2026-11-18T09:11:00Z',
            ])
            ->where('summary.activity.data.1', [
                'kind' => 'purchase',
                'amount' => ['user' => '$10.0000', 'exact' => '$10.000000000'],
                'prompt' => null,
                'createdAt' => '2026-11-18T09:00:00Z',
            ]));
});

it('lists 20 per page, with previous and next links', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(1_000_000_000)->count(21)->create();

    $this->actingAs($user)->get(route('credits'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('summary.activity.data', 20)
            ->where('summary.activity.next_page_url', route('credits', ['page' => 2])));
});

it('shows a running generation\'s hold as held, and no longer once it is final', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(10_000_000_000)->create();
    $running = Generation::factory()->for($user)->withHold()->create([
        'status' => GenerationStatus::Processing,
        'estimate_maximum' => Money::fromNanodollars(467_343_750),
    ]);

    $this->actingAs($user)->get(route('credits'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.held', ['user' => '$0.4673', 'exact' => '$0.467343750']));

    ledgerRow($user, CreditTransactionType::Release, 467_343_750, $running);

    $this->actingAs($user)->get(route('credits'))
        ->assertInertia(fn (Assert $page) => $page->where('summary.held', null));
});

it('makes the purchases and charges, less the held amount, add up to the balance', function () {
    $user = User::factory()->create();
    $done = Generation::factory()->for($user)->create(['status' => GenerationStatus::Succeeded]);
    $running = Generation::factory()->for($user)->create(['status' => GenerationStatus::Processing]);
    ledgerRow($user, CreditTransactionType::Purchase, 10_000_000_000);
    ledgerRow($user, CreditTransactionType::Hold, -467_343_750, $done);
    ledgerRow($user, CreditTransactionType::Release, 467_343_750, $done);
    ledgerRow($user, CreditTransactionType::Charge, -34_500_000, $done);
    ledgerRow($user, CreditTransactionType::Hold, -86_250_000, $running);

    // $10 − $0.0345 charged − $0.08625 held = $9.87925.
    $this->actingAs($user)->get(route('credits'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('balance.amount.exact', '$9.879250000')
            ->where('summary.held.exact', '$0.086250000')
            ->where('summary.activity.data.0.amount.exact', '-$0.034500000')
            ->where('summary.activity.data.1.amount.exact', '$10.000000000'));
});

it('never shows another user\'s activity or held amount', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    CreditTransaction::factory()->for($other)->purchase(10_000_000_000)->create();
    Generation::factory()->for($other)->withHold()->create(['status' => GenerationStatus::Processing]);

    $this->actingAs($user)->get(route('credits'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('summary.activity.data', 0)
            ->where('summary.held', null)
            ->where('balance.amount.exact', '$0.000000000'));
});

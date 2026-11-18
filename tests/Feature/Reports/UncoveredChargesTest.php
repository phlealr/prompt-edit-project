<?php

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-11-18 12:00:00');
    $this->operator = User::factory()->create(['is_admin' => true]);
    fakeImageProvider();
});

/**
 * A succeeded generation with its hold, release and charge, as ApplyProviderUpdate writes them.
 */
function settled(User $user, string $completedAt, int $hold, int $charge): Generation
{
    $generation = Generation::factory()->for($user)->create([
        'status' => GenerationStatus::Succeeded,
        'provider_cost' => Money::fromNanodollars(intdiv($charge * 4, 5)),
        'completed_at' => $completedAt,
    ]);

    foreach ([[CreditTransactionType::Hold, -$hold], [CreditTransactionType::Release, $hold], [CreditTransactionType::Charge, -$charge]] as [$type, $amount]) {
        CreditTransaction::factory()->for($user)->create([
            'type' => $type, 'generation_id' => $generation->id, 'amount' => Money::fromNanodollars($amount),
        ]);
    }

    return $generation;
}

it('lists the charges above their hold in the range, with the uncovered amount and its total', function () {
    $carol = User::factory()->create(['email' => 'carol@example.test']);
    settled($carol, '2026-11-17 09:00:00', hold: 400_000_000, charge: 467_343_750);
    settled($carol, '2026-11-17 10:00:00', hold: 500_000_000, charge: 467_343_750); // covered
    settled($carol, '2026-11-01 10:00:00', hold: 1, charge: 2); // outside the range

    $this->actingAs($this->operator)
        ->get(route('operator-report', ['start' => '2026-11-10', 'end' => '2026-11-18']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.uncoveredCharges', 1)
            ->where('report.uncoveredCharges.0', [
                'email' => 'carol@example.test',
                'date' => '2026-11-17',
                'hold' => ['user' => '$0.4000', 'exact' => '$0.400000000'],
                'charge' => ['user' => '$0.4673', 'exact' => '$0.467343750'],
                'uncovered' => ['user' => '$0.0673', 'exact' => '$0.067343750'],
            ])
            ->where('report.uncoveredTotal.exact', '$0.067343750'));
});

it('lists the users whose balance is below zero now, with their balance', function () {
    $dave = User::factory()->create(['email' => 'dave@example.test']);
    settled($dave, '2026-11-17 09:00:00', hold: 400_000_000, charge: 467_343_750); // balance −$0.467343750
    $erin = User::factory()->create(['email' => 'erin@example.test']);
    CreditTransaction::factory()->for($erin)->purchase(1_000_000_000)->create();

    $this->actingAs($this->operator)->get(route('operator-report'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.negativeBalances', 1)
            ->where('report.negativeBalances.0', [
                'email' => 'dave@example.test',
                'balance' => ['user' => '-$0.4673', 'exact' => '-$0.467343750'],
            ]));
});

it('has no uncovered charges when every charge was covered', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(1_000_000_000)->create();
    settled($user, '2026-11-17 09:00:00', hold: 500_000_000, charge: 467_343_750);

    $this->actingAs($this->operator)->get(route('operator-report'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.uncoveredCharges', 0)
            ->has('report.negativeBalances', 0)
            ->where('report.uncoveredTotal.exact', '$0.000000000'));
});

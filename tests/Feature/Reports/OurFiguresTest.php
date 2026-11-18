<?php

use App\Enums\CreditTransactionType;
use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-11-18 12:00:00');
    $this->operator = User::factory()->create(['is_admin' => true]);
});

/**
 * A succeeded generation completed at $completedAt, with its provider cost and its charge.
 */
function completed(User $user, string $completedAt, int $providerCost, int $charge, string $createdAt = '2026-11-01 00:00:00'): Generation
{
    $generation = Generation::factory()->for($user)->create([
        'status' => GenerationStatus::Succeeded,
        'provider_cost' => Money::fromNanodollars($providerCost),
        'completed_at' => $completedAt,
        'created_at' => $createdAt,
    ]);
    CreditTransaction::factory()->for($user)->create([
        'type' => CreditTransactionType::Charge,
        'generation_id' => $generation->id,
        'amount' => Money::fromNanodollars(-$charge),
    ]);

    return $generation;
}

it('covers the last 30 UTC days ending today when no dates are given, with a row for every day', function () {
    $this->actingAs($this->operator)->get(route('operator-report'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.start', '2026-10-20')
            ->where('report.end', '2026-11-18')
            ->has('report.days', 30)
            ->where('report.days.0.date', '2026-11-18')
            ->where('report.days.29.date', '2026-10-20')
            ->where('report.days.5.figures', [
                'generations' => 0,
                'userSpend' => ['user' => '$0.0000', 'exact' => '$0.000000000'],
                'providerCost' => ['user' => '$0.0000', 'exact' => '$0.000000000'],
                'profit' => ['user' => '$0.0000', 'exact' => '$0.000000000'],
            ]));
});

it('accepts a range longer than 92 days and shows every day', function () {
    $this->actingAs($this->operator)
        ->get(route('operator-report', ['start' => '2026-01-01', 'end' => '2026-07-19']))
        ->assertInertia(fn (Assert $page) => $page->has('report.days', 200));
});

it('rejects a start after the end with its message', function () {
    $this->actingAs($this->operator)
        ->from(route('operator-report'))
        ->get(route('operator-report', ['start' => '2026-11-10', 'end' => '2026-11-01']))
        ->assertSessionHasErrors(['end' => 'The start date must be on or before the end date.']);
});

it('counts a generation on the UTC day it completed, not the day it was created', function () {
    completed(User::factory()->create(), '2026-11-17 00:01:00', 100, 125, createdAt: '2026-11-16 23:59:00');

    $this->actingAs($this->operator)
        ->get(route('operator-report', ['start' => '2026-11-16', 'end' => '2026-11-17']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.days.0.date', '2026-11-17')
            ->where('report.days.0.figures.generations', 1)
            ->where('report.days.1.figures.generations', 0));
});

it('adds nothing for failed generations', function () {
    Generation::factory()->for(User::factory())->create([
        'status' => GenerationStatus::Failed,
        'failure_reason' => FailureReason::ProviderFailed,
        'completed_at' => '2026-11-17 10:00:00',
    ]);

    $this->actingAs($this->operator)
        ->get(route('operator-report', ['start' => '2026-11-17', 'end' => '2026-11-17']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.totals.generations', 0)
            ->where('report.days.0.figures.generations', 0));
});

it('sums spend from charges and provider cost from recorded costs, with profit their difference, per day, per user and in total', function () {
    $alice = User::factory()->create(['email' => 'alice@example.test']);
    $bob = User::factory()->create(['email' => 'bob@example.test']);
    // The worked example: provider cost $0.373875000, charge $0.467343750.
    completed($alice, '2026-11-17 09:00:00', 373_875_000, 467_343_750);
    completed($alice, '2026-11-17 10:00:00', 100_000_000, 125_000_000);
    completed($bob, '2026-11-17 11:00:00', 4, 5);
    completed($bob, '2026-11-18 08:00:00', 1, 2);

    $this->actingAs($this->operator)
        ->get(route('operator-report', ['start' => '2026-11-17', 'end' => '2026-11-18']))
        ->assertInertia(fn (Assert $page) => $page
            // 17 Nov: spend 0.467343750 + 0.125 + 0.000000005 = 0.592343755;
            // cost 0.373875 + 0.1 + 0.000000004 = 0.473875004; profit 0.118468751.
            ->where('report.days.1.date', '2026-11-17')
            ->where('report.days.1.figures.generations', 3)
            ->where('report.days.1.figures.userSpend.exact', '$0.592343755')
            ->where('report.days.1.figures.providerCost.exact', '$0.473875004')
            ->where('report.days.1.figures.profit.exact', '$0.118468751')
            // Per user under 17 Nov, highest spend first; they add up to the day.
            ->where('report.days.1.users.0.email', 'alice@example.test')
            ->where('report.days.1.users.0.figures.userSpend.exact', '$0.592343750')
            ->where('report.days.1.users.1.email', 'bob@example.test')
            ->where('report.days.1.users.1.figures.userSpend.exact', '$0.000000005')
            // Totals: spend 0.592343757, cost 0.473875005, profit 0.118468752.
            ->where('report.totals.generations', 4)
            ->where('report.totals.userSpend.exact', '$0.592343757')
            ->where('report.totals.providerCost.exact', '$0.473875005')
            ->where('report.totals.profit.exact', '$0.118468752')
            // Per user for the range; they add up to the totals.
            ->where('report.users.0.email', 'alice@example.test')
            ->where('report.users.0.figures.generations', 2)
            ->where('report.users.0.figures.profit.exact', '$0.118468750')
            ->where('report.users.1.email', 'bob@example.test')
            ->where('report.users.1.figures.generations', 2)
            ->where('report.users.1.figures.userSpend.exact', '$0.000000007')
            ->where('report.users.1.figures.profit.exact', '$0.000000002'));
});

it('rejects a start after today when no end is given, with the same message', function () {
    $this->actingAs($this->operator)
        ->from(route('operator-report'))
        ->get(route('operator-report', ['start' => '2026-12-05']))
        ->assertSessionHasErrors(['end' => 'The start date must be on or before the end date.']);
});

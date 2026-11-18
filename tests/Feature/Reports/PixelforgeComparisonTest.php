<?php

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\ImageProviders\ProviderUsage;
use App\ImageProviders\UsageDay;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-11-18 14:05:00');
    $this->operator = User::factory()->create(['is_admin' => true]);
    $this->provider = fakeImageProvider();
    // Ours: $0.373875000 of provider cost on 17 Nov.
    $generation = Generation::factory()->for(User::factory())->create([
        'status' => GenerationStatus::Succeeded,
        'provider_cost' => Money::fromNanodollars(373_875_000),
        'completed_at' => '2026-11-17 09:00:00',
    ]);
    CreditTransaction::factory()->for($generation->user)->create([
        'type' => CreditTransactionType::Charge, 'generation_id' => $generation->id, 'amount' => Money::fromNanodollars(-467_343_750),
    ]);
});

function showReport(): TestResponse
{
    return test()->actingAs(test()->operator)->get(route('operator-report', ['start' => '2026-11-17', 'end' => '2026-11-18']));
}

it('shows Pixelforge\'s cost for each day and the difference from ours, highlighting a non-zero one', function () {
    $this->provider->usage = new ProviderUsage([
        new UsageDay('2026-11-17', Money::fromNanodollars(373_000_000)),
        new UsageDay('2026-11-18', Money::fromNanodollars(0)),
    ], CarbonImmutable::parse('2026-11-18 14:03:00'));

    showReport()->assertInertia(fn (Assert $page) => $page
        ->where('report.providerFiguresAsOf', '2026-11-18T14:03:00Z')
        ->where('report.providerUnavailable', false)
        ->where('report.days.0.date', '2026-11-18')
        ->where('report.days.0.pixelforgeCost.exact', '$0.000000000')
        ->where('report.days.0.difference.exact', '$0.000000000')
        ->where('report.days.0.highlighted', false)
        // 0.373875000 − 0.373000000 = 0.000875000.
        ->where('report.days.1.pixelforgeCost.exact', '$0.373000000')
        ->where('report.days.1.difference.exact', '$0.000875000')
        ->where('report.days.1.highlighted', true));
});

it('asks the provider for the report\'s whole range', function () {
    showReport();

    expect($this->provider->usageAskedFor)->toBe('2026-11-17 2026-11-18');
});

it('shows our figures alone, marked unavailable, when the provider\'s usage cannot be fetched', function () {
    $this->provider->usageUnavailable = true;

    showReport()->assertInertia(fn (Assert $page) => $page
        ->where('report.providerUnavailable', true)
        ->where('report.providerFiguresAsOf', null)
        ->where('report.days.1.figures.providerCost.exact', '$0.373875000')
        ->where('report.days.1.pixelforgeCost', null)
        ->where('report.days.1.difference', null)
        ->where('report.days.1.highlighted', false));
});

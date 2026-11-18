<?php

use App\Enums\CreditTransactionType;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

it('shares the balance with every signed-in page, with 4 decimals and the exact amount', function (string $route) {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(5_123_456_789)->create();
    fakeImageProvider();

    $this->actingAs($user)->get(route($route))
        ->assertInertia(fn (Assert $page) => $page->where('balance', [
            'amount' => ['user' => '$5.1235', 'exact' => '$5.123456789'],
            'belowZero' => false,
        ]));
})->with(['generate', 'history', 'credits', 'profile.edit']);

it('shares no balance with guests', function () {
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('balance', null));
});

it('gives a new user a balance of $0.0000 and an empty ledger', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('history'))
        ->assertInertia(fn (Assert $page) => $page->where('balance.amount.user', '$0.0000'));
    expect(CreditTransaction::query()->whereBelongsTo($user)->exists())->toBeFalse();
});

it('computes the balance from the ledger, with no column storing it', function () {
    expect(Schema::hasColumn('users', 'balance'))->toBeFalse();
});

it('marks a balance below zero', function () {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->create();
    CreditTransaction::factory()->for($user)->create([
        'type' => CreditTransactionType::Charge,
        'generation_id' => $generation->id,
        'amount' => Money::fromNanodollars(-1),
    ]);

    $this->actingAs($user)->get(route('credits'))
        ->assertInertia(fn (Assert $page) => $page->where('balance.belowZero', true));
});

it('sends the current balance with each history reload', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(1_000_000_000)->create();
    $version = app(HandleInertiaRequests::class)->version(request());

    $this->actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
            'X-Inertia-Partial-Component' => 'generations/Index',
            'X-Inertia-Partial-Data' => 'generations,balance',
        ])
        ->get(route('history'))
        ->assertJsonPath('props.balance.amount.user', '$1.0000');
});

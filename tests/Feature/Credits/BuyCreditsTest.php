<?php

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the Credits page with the balance', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(10_000_000_000)->create();

    $this->actingAs($user)->get(route('credits'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('credits/Index')
            ->where('balance.amount', ['user' => '$10.0000', 'exact' => '$10.000000000']));
});

it('buys 10 dollars as exactly one purchase of 10,000,000,000 nanodollars, and says so', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('credits'))
        ->post(route('credits.store'), ['amount' => '10'])
        ->assertRedirect(route('credits'))
        ->assertInertiaFlash('purchase', 'Added $10.0000 to your balance');

    $purchase = CreditTransaction::query()->sole();
    expect($purchase->user_id)->toBe($user->id)
        ->and($purchase->type)->toBe(CreditTransactionType::Purchase)
        ->and($purchase->generation_id)->toBeNull()
        ->and($purchase->amount->nanodollars())->toBe(10_000_000_000);
});

it('accepts 1 and 1,000 dollars', function (string $amount, int $nanodollars) {
    $this->actingAs(User::factory()->create())->post(route('credits.store'), ['amount' => $amount])
        ->assertSessionHasNoErrors();

    expect(CreditTransaction::query()->sole()->amount->nanodollars())->toBe($nanodollars);
})->with([
    'one dollar' => ['1', 1_000_000_000],
    'a thousand dollars' => ['1000', 1_000_000_000_000],
]);

it('rejects any other amount with the field\'s message, and buys nothing', function (mixed $amount) {
    $this->actingAs(User::factory()->create())->post(route('credits.store'), ['amount' => $amount])
        ->assertSessionHasErrors(['amount' => 'Enter a whole number of dollars between 1 and 1,000.']);

    expect(CreditTransaction::query()->count())->toBe(0);
})->with([
    'zero' => ['0'],
    'negative' => ['-5'],
    'cents' => ['10.50'],
    'over a thousand' => ['1001'],
    'empty' => [''],
    'not a number' => ['ten'],
]);

it('does not let a guest buy credits', function () {
    $this->post(route('credits.store'), ['amount' => '10'])->assertRedirect(route('login'));
    $this->get(route('credits'))->assertRedirect(route('login'));

    expect(CreditTransaction::query()->count())->toBe(0);
});

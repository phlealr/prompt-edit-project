<?php

use App\Actions\Credits\CalculateBalance;
use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Database\UniqueConstraintViolationException;

it('gives a new user an empty ledger and a balance of $0.0000', function () {
    $user = User::factory()->create();

    expect(CreditTransaction::query()->whereBelongsTo($user)->count())->toBe(0)
        ->and(app(CalculateBalance::class)->handle($user)->forUser())->toBe('$0.0000');
});

it('computes the balance as the sum of the user\'s ledger rows only', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(10_000_000_000)->create();
    CreditTransaction::factory()->for($user)->create(['type' => CreditTransactionType::Hold, 'amount' => Money::fromNanodollars(-467_343_750), 'generation_id' => Generation::factory()->for($user)->create()->id]);
    CreditTransaction::factory()->for(User::factory())->purchase(5_000_000_000)->create();

    // $10 − $0.467343750 = $9.532656250, and the other user's purchase does not count.
    expect(app(CalculateBalance::class)->handle($user)->nanodollars())->toBe(9_532_656_250);
});

it('refuses to update a ledger row', function () {
    $row = CreditTransaction::factory()->purchase(10_000_000_000)->create();

    expect(fn () => $row->update(['amount' => Money::fromNanodollars(1)]))->toThrow(LogicException::class)
        ->and($row->fresh()?->amount->nanodollars())->toBe(10_000_000_000);
});

it('refuses to delete a ledger row', function () {
    $row = CreditTransaction::factory()->purchase(10_000_000_000)->create();

    expect(fn () => $row->delete())->toThrow(LogicException::class)
        ->and(CreditTransaction::query()->whereKey($row->id)->exists())->toBeTrue();
});

it('has the database refuse a second row of the same type for the same generation', function () {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->create();
    CreditTransaction::factory()->for($user)->create(['type' => CreditTransactionType::Hold, 'amount' => Money::fromNanodollars(-100), 'generation_id' => $generation->id]);

    CreditTransaction::factory()->for($user)->create(['type' => CreditTransactionType::Hold, 'amount' => Money::fromNanodollars(-100), 'generation_id' => $generation->id]);
})->throws(UniqueConstraintViolationException::class);

it('allows one row of each type for the same generation', function () {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->create();

    foreach ([CreditTransactionType::Hold, CreditTransactionType::Release, CreditTransactionType::Charge] as $type) {
        CreditTransaction::factory()->for($user)->create(['type' => $type, 'amount' => Money::fromNanodollars(100), 'generation_id' => $generation->id]);
    }

    expect(CreditTransaction::query()->where('generation_id', $generation->id)->count())->toBe(3);
});

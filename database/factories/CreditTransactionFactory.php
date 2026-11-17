<?php

namespace Database\Factories;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'generation_id' => null,
            'type' => CreditTransactionType::Purchase,
            'amount' => Money::fromNanodollars(10_000_000_000),
        ];
    }

    /**
     * A purchase of this many nanodollars.
     */
    public function purchase(int $nanodollars): static
    {
        return $this->state(fn (): array => [
            'type' => CreditTransactionType::Purchase,
            'amount' => Money::fromNanodollars($nanodollars),
        ]);
    }
}

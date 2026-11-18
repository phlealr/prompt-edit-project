<?php

namespace Database\Factories;

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Enums\ImageProviderName;
use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Rates;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    /**
     * A pending generation at the published rates.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'provider' => ImageProviderName::Pixelforge,
            'prompt' => fake()->sentence(),
            'size' => Size::Square,
            'quality' => Quality::Medium,
            'reference_images' => [],
            'status' => GenerationStatus::Pending,
            'rates' => new Rates(
                Money::fromDecimalString('5.00'),
                Money::fromDecimalString('10.00'),
                Money::fromDecimalString('40.00'),
                Money::fromDecimalString('12.50'),
            ),
            'estimate_minimum' => Money::fromNanodollars(58_125_000),
            'estimate_maximum' => Money::fromNanodollars(86_250_000),
        ];
    }

    /**
     * With its hold in the ledger: minus its estimate's maximum, as a submission places it.
     */
    public function withHold(): static
    {
        return $this->afterCreating(function (Generation $generation): void {
            CreditTransaction::query()->create([
                'user_id' => $generation->user_id,
                'generation_id' => $generation->id,
                'type' => CreditTransactionType::Hold,
                'amount' => $generation->estimate_maximum->negate(),
            ]);
        });
    }
}

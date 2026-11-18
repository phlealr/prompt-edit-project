<?php

namespace App\Actions\Generations;

use App\Actions\Credits\CalculateBalance;
use App\Data\Generations\EstimateForUser;
use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\ImageProvider;
use App\Models\User;

class EstimateGeneration
{
    public function __construct(
        private ImageProvider $provider,
        private CalculateBalance $calculateBalance,
    ) {}

    /**
     * The range of possible charges for this input, from the provider's current pricing (ADR 0011),
     * with the user's balance and whether it covers the estimate's maximum (ADR 0017).
     *
     * @param  list<string>  $referenceImages
     *
     * @throws PricingUnavailable
     */
    public function handle(User $user, string $prompt, Size $size, Quality $quality, array $referenceImages): EstimateForUser
    {
        $estimate = $this->provider->estimate($prompt, $size, $quality, $referenceImages);
        $balance = $this->calculateBalance->handle($user);

        return new EstimateForUser(
            $estimate->minimum,
            $estimate->maximum,
            $balance,
            $balance->nanodollars() >= $estimate->maximum->nanodollars(),
        );
    }
}

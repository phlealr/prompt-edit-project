<?php

namespace App\Actions\Generations;

use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Estimate;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\ImageProvider;

class EstimateGeneration
{
    public function __construct(private ImageProvider $provider) {}

    /**
     * The range of possible charges for this input, from the provider's current pricing (ADR 0011).
     *
     * @param  list<string>  $referenceImages
     *
     * @throws PricingUnavailable
     */
    public function handle(string $prompt, Size $size, Quality $quality, array $referenceImages): Estimate
    {
        return $this->provider->estimate($prompt, $size, $quality, $referenceImages);
    }
}

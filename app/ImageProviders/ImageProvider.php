<?php

namespace App\ImageProviders;

use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Exceptions\PricingUnavailable;

/**
 * An external image-generation service (ADR 0014).
 */
interface ImageProvider
{
    /**
     * @param  list<string>  $referenceImages  Their URLs; a URL given twice is billed twice.
     *
     * @throws PricingUnavailable
     */
    public function estimate(string $prompt, Size $size, Quality $quality, array $referenceImages): Estimate;
}

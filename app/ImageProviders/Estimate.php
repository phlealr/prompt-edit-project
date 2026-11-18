<?php

namespace App\ImageProviders;

use App\ValueObjects\Money;

/**
 * The range of what the provider can bill for a generation, and the rates it used (ADR 0011). Our
 * markup is added by the actions, not by the provider (ADR 0045).
 */
final readonly class Estimate
{
    public function __construct(
        public Money $minimumCost,
        public Money $maximumCost,
        public Rates $rates,
    ) {}
}

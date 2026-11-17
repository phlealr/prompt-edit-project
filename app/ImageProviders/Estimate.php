<?php

namespace App\ImageProviders;

use App\ValueObjects\Money;

/**
 * The range of possible charges for a generation, markup included (ADR 0011).
 */
final readonly class Estimate
{
    public function __construct(
        public Money $minimum,
        public Money $maximum,
        public Rates $rates,
    ) {}
}

<?php

namespace App\ImageProviders;

use App\ValueObjects\Money;

/**
 * What the provider billed on one UTC day.
 */
final readonly class UsageDay
{
    /**
     * @param  string  $date  The UTC day, as YYYY-MM-DD.
     */
    public function __construct(
        public string $date,
        public Money $cost,
    ) {}
}

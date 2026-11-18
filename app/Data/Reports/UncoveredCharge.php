<?php

namespace App\Data\Reports;

use App\ValueObjects\Money;

/**
 * A charge above its generation's hold: the provider's rates rose after the rate check (ADR 0026).
 */
final readonly class UncoveredCharge
{
    /**
     * @param  string  $date  The UTC day the generation completed, as YYYY-MM-DD.
     */
    public function __construct(
        public string $email,
        public string $date,
        public Money $hold,
        public Money $charge,
        public Money $uncovered,
    ) {}
}

<?php

namespace App\Data\Generations;

use App\ValueObjects\Money;

/**
 * An estimate as the generate form shows it: the range of charges, the user's balance, and whether
 * the balance covers the maximum (ADR 0011, ADR 0017).
 */
final readonly class EstimateForUser
{
    public function __construct(
        public Money $minimum,
        public Money $maximum,
        public Money $balance,
        public bool $enoughCredits,
    ) {}
}

<?php

namespace App\Data\Reports;

use App\ValueObjects\Money;

/**
 * A user whose balance is below zero now, after an uncovered charge (ADR 0026).
 */
final readonly class NegativeBalance
{
    public function __construct(
        public string $email,
        public Money $balance,
    ) {}
}

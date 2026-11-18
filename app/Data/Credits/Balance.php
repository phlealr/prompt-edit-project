<?php

namespace App\Data\Credits;

use App\ValueObjects\Money;

/**
 * The user's balance as every page shows it. Below zero, the page shows it in the error colour; the
 * flag comes from here so the frontend never compares amounts (ADR 0007).
 */
final readonly class Balance
{
    public function __construct(
        public Money $amount,
        public bool $belowZero,
    ) {}

    public static function of(Money $amount): self
    {
        return new self($amount, $amount->nanodollars() < 0);
    }
}

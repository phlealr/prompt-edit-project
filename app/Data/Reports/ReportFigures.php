<?php

namespace App\Data\Reports;

use App\ValueObjects\Money;

/**
 * The operator report's figures for one row: a day, a user, or the totals (ADR 0016).
 */
final readonly class ReportFigures
{
    public function __construct(
        public int $generations,
        public Money $userSpend,
        public Money $providerCost,
        public Money $profit,
    ) {}

    /**
     * Profit is user spend minus provider cost.
     */
    public static function of(int $generations, Money $userSpend, Money $providerCost): self
    {
        return new self(
            $generations,
            $userSpend,
            $providerCost,
            Money::fromNanodollars($userSpend->nanodollars() - $providerCost->nanodollars()),
        );
    }
}

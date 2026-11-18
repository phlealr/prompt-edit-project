<?php

namespace App\Data\Reports;

use App\ValueObjects\Money;

/**
 * Our own figures for a range of UTC days (ADR 0016, ADR 0030).
 */
final readonly class OperatorReport
{
    /**
     * @param  list<DayReport>  $days  Every day of the range, newest first.
     * @param  list<UserFigures>  $users  Every user with generations in the range, highest spend first.
     * @param  string|null  $providerFiguresAsOf  When Pixelforge's figures were fetched, in ISO 8601.
     * @param  bool  $providerUnavailable  Pixelforge's usage could not be fetched; ours are shown alone.
     * @param  list<UncoveredCharge>  $uncoveredCharges  Charges above their hold in the range.
     * @param  Money  $uncoveredTotal  Their uncovered amounts, summed.
     * @param  list<NegativeBalance>  $negativeBalances  Users below zero now, whatever the range.
     */
    public function __construct(
        public string $start,
        public string $end,
        public ReportFigures $totals,
        public array $days,
        public array $users,
        public ?string $providerFiguresAsOf,
        public bool $providerUnavailable,
        public array $uncoveredCharges,
        public Money $uncoveredTotal,
        public array $negativeBalances,
    ) {}
}

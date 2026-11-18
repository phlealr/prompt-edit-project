<?php

namespace App\Data\Reports;

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
     */
    public function __construct(
        public string $start,
        public string $end,
        public ReportFigures $totals,
        public array $days,
        public array $users,
        public ?string $providerFiguresAsOf,
        public bool $providerUnavailable,
    ) {}
}

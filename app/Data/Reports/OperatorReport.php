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
     */
    public function __construct(
        public string $start,
        public string $end,
        public ReportFigures $totals,
        public array $days,
        public array $users,
    ) {}
}

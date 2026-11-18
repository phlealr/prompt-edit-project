<?php

namespace App\Data\Reports;

/**
 * One UTC day of the operator report, with its per-user rows.
 */
final readonly class DayReport
{
    /**
     * @param  string  $date  The UTC day, as YYYY-MM-DD.
     * @param  list<UserFigures>  $users  Highest spend first.
     */
    public function __construct(
        public string $date,
        public ReportFigures $figures,
        public array $users,
    ) {}
}

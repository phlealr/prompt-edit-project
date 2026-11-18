<?php

namespace App\Data\Reports;

use App\ValueObjects\Money;

/**
 * One UTC day of the operator report, with its per-user rows.
 */
final readonly class DayReport
{
    /**
     * @param  string  $date  The UTC day, as YYYY-MM-DD.
     * @param  list<UserFigures>  $users  Highest spend first.
     * @param  Money|null  $pixelforgeCost  What Pixelforge billed that day; null when unavailable.
     * @param  Money|null  $difference  Our provider cost minus Pixelforge's; null when unavailable.
     * @param  bool  $highlighted  The difference is not zero.
     */
    public function __construct(
        public string $date,
        public ReportFigures $figures,
        public array $users,
        public ?Money $pixelforgeCost,
        public ?Money $difference,
        public bool $highlighted,
    ) {}
}

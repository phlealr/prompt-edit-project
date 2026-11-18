<?php

namespace App\Data\Credits;

use App\ValueObjects\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * What the Credits page shows below the balance: the amount held for running generations and the
 * activity.
 */
final readonly class CreditsSummary
{
    /**
     * @param  Money|null  $held  Null when nothing is held.
     * @param  LengthAwarePaginator<int, ActivityEntry>  $activity
     */
    public function __construct(
        public ?Money $held,
        public LengthAwarePaginator $activity,
    ) {}
}

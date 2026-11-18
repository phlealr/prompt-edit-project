<?php

namespace App\ImageProviders;

use App\ValueObjects\Money;
use Carbon\CarbonImmutable;

/**
 * The provider's own report of what it billed per UTC day, to reconcile ours against (ADR 0016).
 */
final readonly class ProviderUsage
{
    /**
     * @param  list<UsageDay>  $days
     * @param  CarbonImmutable  $fetchedAt  When these figures were fetched (they may be cached).
     */
    public function __construct(
        public array $days,
        public CarbonImmutable $fetchedAt,
    ) {}

    public function costOn(string $date): ?Money
    {
        foreach ($this->days as $day) {
            if ($day->date === $date) {
                return $day->cost;
            }
        }

        return null;
    }
}

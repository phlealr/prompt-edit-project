<?php

namespace App\Actions\Reports;

use App\Data\Reports\DayReport;
use App\Data\Reports\OperatorReport;
use App\Data\Reports\ReportFigures;
use App\Data\Reports\UserFigures;
use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class BuildOperatorReport
{
    /**
     * Our figures for the UTC days from $start to $end, inclusive. A generation counts on the UTC day
     * the provider completed it, the day the provider bills it on; failed ones count nowhere
     * (ADR 0016).
     */
    public function handle(CarbonImmutable $start, CarbonImmutable $end): OperatorReport
    {
        $generations = Generation::query()
            ->where('status', GenerationStatus::Succeeded)
            ->where('completed_at', '>=', $start->startOfDay())
            ->where('completed_at', '<', $end->addDay()->startOfDay())
            ->with(['charge', 'user'])
            ->get();

        // Grouped once by UTC completion day, then every day of the range gets a row.
        $byDay = $generations->groupBy(fn (Generation $generation): string => (string) $generation->completed_at?->utc()->toDateString());
        $days = [];

        for ($day = $end; $day->gte($start); $day = $day->subDay()) {
            $ofDay = $byDay->get($day->toDateString(), new Collection);
            $days[] = new DayReport($day->toDateString(), $this->figures($ofDay), $this->perUser($ofDay));
        }

        return new OperatorReport(
            $start->toDateString(),
            $end->toDateString(),
            $this->figures($generations),
            $days,
            $this->perUser($generations),
        );
    }

    /**
     * @param  Collection<int, Generation>  $generations
     */
    private function figures(Collection $generations): ReportFigures
    {
        // A charge row is negative in the ledger; spend is what users paid.
        $userSpend = $generations->sum(fn (Generation $generation): int => -($generation->charge?->amount->nanodollars() ?? 0));
        $providerCost = $generations->sum(fn (Generation $generation): int => $generation->provider_cost?->nanodollars() ?? 0);

        return ReportFigures::of($generations->count(), Money::fromNanodollars($userSpend), Money::fromNanodollars($providerCost));
    }

    /**
     * @param  Collection<int, Generation>  $generations
     * @return list<UserFigures>
     */
    private function perUser(Collection $generations): array
    {
        return array_values($generations
            ->groupBy('user_id')
            ->map(fn (Collection $ofUser): UserFigures => new UserFigures(
                $ofUser->first()->user->email,
                $this->figures($ofUser),
            ))
            ->sortByDesc(fn (UserFigures $user): int => $user->figures->userSpend->nanodollars())
            ->all());
    }
}

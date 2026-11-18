<?php

namespace App\Actions\Reports;

use App\Data\Reports\DayReport;
use App\Data\Reports\NegativeBalance;
use App\Data\Reports\OperatorReport;
use App\Data\Reports\ReportFigures;
use App\Data\Reports\UncoveredCharge;
use App\Data\Reports\UserFigures;
use App\Enums\GenerationStatus;
use App\ImageProviders\Exceptions\UsageUnavailable;
use App\ImageProviders\ImageProvider;
use App\Models\Generation;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildOperatorReport
{
    public function __construct(private ImageProvider $provider) {}

    /**
     * Our figures for the UTC days from $start to $end, inclusive, beside the provider's own cost.
     * A generation counts on the UTC day the provider completed it, the day the provider bills it
     * on; failed ones count nowhere (ADR 0016). Without the provider's usage, ours are shown alone
     * (ADR 0030).
     */
    public function handle(CarbonImmutable $start, CarbonImmutable $end): OperatorReport
    {
        $generations = Generation::query()
            ->where('status', GenerationStatus::Succeeded)
            ->where('completed_at', '>=', $start->startOfDay())
            ->where('completed_at', '<', $end->addDay()->startOfDay())
            ->with(['charge', 'hold', 'user'])
            ->orderBy('completed_at')
            ->get();

        try {
            $usage = $this->provider->usage($start, $end);
        } catch (UsageUnavailable) {
            $usage = null;
        }

        // Grouped once by UTC completion day, then every day of the range gets a row.
        $byDay = $generations->groupBy(fn (Generation $generation): string => (string) $generation->completed_at?->utc()->toDateString());
        $days = [];

        for ($day = $end; $day->gte($start); $day = $day->subDay()) {
            $ofDay = $byDay->get($day->toDateString(), new Collection);
            $figures = $this->figures($ofDay);
            $pixelforgeCost = $usage?->costOn($day->toDateString());
            $difference = $pixelforgeCost === null
                ? null
                : Money::fromNanodollars($figures->providerCost->nanodollars() - $pixelforgeCost->nanodollars());

            $days[] = new DayReport(
                $day->toDateString(),
                $figures,
                $this->perUser($ofDay),
                $pixelforgeCost,
                $difference,
                highlighted: $difference !== null && $difference->nanodollars() !== 0,
            );
        }

        $uncoveredCharges = $this->uncoveredCharges($generations);
        $uncoveredTotal = 0;

        foreach ($uncoveredCharges as $uncoveredCharge) {
            $uncoveredTotal += $uncoveredCharge->uncovered->nanodollars();
        }

        return new OperatorReport(
            $start->toDateString(),
            $end->toDateString(),
            $this->figures($generations),
            $days,
            $this->perUser($generations),
            $usage?->fetchedAt->utc()->toIso8601ZuluString(),
            providerUnavailable: $usage === null,
            uncoveredCharges: $uncoveredCharges,
            uncoveredTotal: Money::fromNanodollars($uncoveredTotal),
            negativeBalances: $this->negativeBalances(),
        );
    }

    /**
     * The generations whose charge exceeded their hold, found from the ledger alone (ADR 0026).
     *
     * @param  Collection<int, Generation>  $generations
     * @return list<UncoveredCharge>
     */
    private function uncoveredCharges(Collection $generations): array
    {
        $uncovered = [];

        foreach ($generations as $generation) {
            $held = $generation->hold?->amount->negate()->nanodollars() ?? 0;
            $charged = $generation->charge?->amount->negate()->nanodollars() ?? 0;

            if ($charged > $held) {
                $uncovered[] = new UncoveredCharge(
                    $generation->user->email,
                    (string) $generation->completed_at?->utc()->toDateString(),
                    Money::fromNanodollars($held),
                    Money::fromNanodollars($charged),
                    Money::fromNanodollars($charged - $held),
                );
            }
        }

        return $uncovered;
    }

    /**
     * The users whose balance, the sum of their ledger rows, is below zero now.
     *
     * @return list<NegativeBalance>
     */
    private function negativeBalances(): array
    {
        return array_values(DB::table('credit_transactions')
            ->join('users', 'users.id', '=', 'credit_transactions.user_id')
            ->groupBy('users.id', 'users.email')
            ->havingRaw('sum(credit_transactions.amount) < 0')
            ->orderBy('users.email')
            ->get(['users.email', DB::raw('sum(credit_transactions.amount) as balance')])
            ->map(fn (object $row): NegativeBalance => new NegativeBalance((string) $row->email, Money::fromNanodollars((int) $row->balance)))
            ->all());
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

<?php

namespace App\Actions\Credits;

use App\Data\Credits\ActivityEntry;
use App\Data\Credits\CreditsSummary;
use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\User;
use App\ValueObjects\Money;

class SummarizeCredits
{
    private const int PER_PAGE = 20;

    /**
     * The held amount and the activity of the user's ledger. Purchases and charges, less the held
     * amount, add up to the balance; released holds cancel out (docs/features/balances.md).
     */
    public function handle(User $user): CreditsSummary
    {
        $activity = CreditTransaction::query()
            ->whereBelongsTo($user)
            ->whereIn('type', [CreditTransactionType::Purchase, CreditTransactionType::Charge])
            ->with('generation')
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->through(fn (CreditTransaction $row): ActivityEntry => ActivityEntry::from($row));

        return new CreditsSummary($this->held($user), $activity);
    }

    /**
     * The holds of the user's generations with no release yet, as a positive amount.
     */
    private function held(User $user): ?Money
    {
        $holds = (int) CreditTransaction::query()
            ->whereBelongsTo($user)
            ->where('type', CreditTransactionType::Hold)
            ->whereNotExists(fn ($query) => $query
                ->from('credit_transactions as releases')
                ->whereColumn('releases.generation_id', 'credit_transactions.generation_id')
                ->where('releases.type', CreditTransactionType::Release->value))
            ->sum('amount');

        return $holds === 0 ? null : Money::fromNanodollars($holds)->negate();
    }
}

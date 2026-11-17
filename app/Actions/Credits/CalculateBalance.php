<?php

namespace App\Actions\Credits;

use App\Models\CreditTransaction;
use App\Models\User;
use App\ValueObjects\Money;

class CalculateBalance
{
    /**
     * A user's balance: the sum of their ledger rows; no column stores it (ADR 0006).
     */
    public function handle(User $user): Money
    {
        return Money::fromNanodollars((int) CreditTransaction::query()->whereBelongsTo($user)->sum('amount'));
    }
}

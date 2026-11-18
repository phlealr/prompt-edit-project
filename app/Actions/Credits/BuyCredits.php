<?php

namespace App\Actions\Credits;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\User;
use App\ValueObjects\Money;

class BuyCredits
{
    /**
     * Adds a purchase of whole dollars to the user's ledger (ADR 0006, ADR 0029).
     */
    public function handle(User $user, int $dollars): Money
    {
        $amount = Money::fromWholeDollars($dollars);

        CreditTransaction::query()->create([
            'user_id' => $user->id,
            'type' => CreditTransactionType::Purchase,
            'amount' => $amount,
        ]);

        return $amount;
    }
}

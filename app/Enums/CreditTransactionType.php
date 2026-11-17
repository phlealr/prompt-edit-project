<?php

namespace App\Enums;

/**
 * The kinds of ledger row (ADR 0006).
 */
enum CreditTransactionType: string
{
    case Purchase = 'purchase';
    case Hold = 'hold';
    case Release = 'release';
    case Charge = 'charge';
}

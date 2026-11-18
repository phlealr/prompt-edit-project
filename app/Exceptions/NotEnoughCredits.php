<?php

namespace App\Exceptions;

use App\ValueObjects\Money;
use RuntimeException;

/**
 * The user's balance does not cover the estimate's maximum (ADR 0006, ADR 0017).
 */
class NotEnoughCredits extends RuntimeException
{
    public function __construct(Money $maximum, Money $balance)
    {
        parent::__construct("Not enough credits: this can cost up to {$maximum->forUser()} and your balance is {$balance->forUser()}");
    }
}

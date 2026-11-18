<?php

namespace App\ImageProviders;

use App\ValueObjects\Money;

/**
 * The provider's price of 1M tokens of each kind, fixed for a generation when its hold is placed
 * (ADR 0026, ADR 0035).
 */
final readonly class Rates
{
    public function __construct(
        public Money $inputTextTokens,
        public Money $inputImageTokens,
        public Money $outputTokens,
        public Money $reasoningTokens,
    ) {}

    /**
     * Equal when every rate is the same amount, however the provider wrote it (ADR 0035).
     */
    public function equals(self $other): bool
    {
        return $this->inputTextTokens->nanodollars() === $other->inputTextTokens->nanodollars()
            && $this->inputImageTokens->nanodollars() === $other->inputImageTokens->nanodollars()
            && $this->outputTokens->nanodollars() === $other->outputTokens->nanodollars()
            && $this->reasoningTokens->nanodollars() === $other->reasoningTokens->nanodollars();
    }
}

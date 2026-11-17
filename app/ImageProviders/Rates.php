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
}

<?php

namespace App\ImageProviders\Pixelforge;

use App\Enums\Quality;

/**
 * The range of reasoning tokens Pixelforge bills for one quality.
 */
final readonly class ReasoningTokens
{
    public function __construct(
        public Quality $quality,
        public int $minimum,
        public int $maximum,
    ) {}
}

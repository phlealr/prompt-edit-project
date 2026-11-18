<?php

namespace App\ImageProviders\Pixelforge;

use App\Enums\Quality;
use App\Enums\Size;

/**
 * The exact output tokens Pixelforge bills for one quality and size.
 */
final readonly class OutputTokens
{
    public function __construct(
        public Quality $quality,
        public Size $size,
        public int $tokens,
    ) {}
}

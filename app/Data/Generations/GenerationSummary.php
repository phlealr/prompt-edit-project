<?php

namespace App\Data\Generations;

use App\Enums\GenerationStatus;
use App\Models\Generation;

/**
 * One generation as the history page lists it.
 */
final readonly class GenerationSummary
{
    public function __construct(
        public int $id,
        public string $prompt,
        public GenerationStatus $status,
    ) {}

    public static function from(Generation $generation): self
    {
        return new self($generation->id, $generation->prompt, $generation->status);
    }
}

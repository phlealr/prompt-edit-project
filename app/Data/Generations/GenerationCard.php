<?php

namespace App\Data\Generations;

use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\Models\Generation;
use App\ValueObjects\Money;

/**
 * One generation as its history card shows it. It carries no provider cost, token count or
 * provider error code: users never see those.
 */
final readonly class GenerationCard
{
    public function __construct(
        public int $id,
        public string $prompt,
        public Size $size,
        public Quality $quality,
        public int $referenceImageCount,
        public GenerationStatus $status,
        public ?string $hint,
        public Money $estimateMinimum,
        public Money $estimateMaximum,
        public ?Money $charge,
        public string $createdAt,
    ) {}

    public static function from(Generation $generation): self
    {
        return new self(
            id: $generation->id,
            prompt: $generation->prompt,
            size: $generation->size,
            quality: $generation->quality,
            referenceImageCount: count($generation->reference_images),
            status: $generation->status,
            hint: $generation->failure_reason?->message() ?? $generation->status->hint(),
            estimateMinimum: $generation->estimate_minimum,
            estimateMaximum: $generation->estimate_maximum,
            // The ledger's charge row is a negative amount; the card shows what was paid.
            charge: $generation->charge?->amount->negate(),
            createdAt: $generation->created_at->utc()->toIso8601ZuluString(),
        );
    }
}

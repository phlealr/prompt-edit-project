<?php

namespace App\Data\Credits;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\ValueObjects\Money;

/**
 * One line of the Credits page's activity: a purchase, or a charge with its generation's prompt.
 */
final readonly class ActivityEntry
{
    public function __construct(
        public CreditTransactionType $kind,
        public Money $amount,
        public ?string $prompt,
        public string $createdAt,
    ) {}

    public static function from(CreditTransaction $row): self
    {
        return new self(
            kind: $row->type,
            amount: $row->amount,
            prompt: $row->generation?->prompt,
            createdAt: $row->created_at->utc()->toIso8601ZuluString(),
        );
    }
}

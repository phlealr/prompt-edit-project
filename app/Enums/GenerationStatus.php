<?php

namespace App\Enums;

/**
 * Where a generation is in its lifecycle; it only moves forward, in this order (ADR 0025).
 */
enum GenerationStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    /**
     * The one-line hint a history card shows under the status (docs/features/history.md). A failed
     * generation shows its failure reason's message instead.
     */
    public function hint(): ?string
    {
        return match ($this) {
            self::Pending => 'Waiting for the image service',
            self::Queued => "In the provider's queue",
            self::Processing => 'Generating…',
            self::Succeeded, self::Failed => null,
        };
    }

    /**
     * Whether a generation in this status may move to $next: only forward. Both final statuses
     * share the last position, so nothing moves out of them (ADR 0025).
     */
    public function canMoveTo(self $next): bool
    {
        return $next->position() > $this->position();
    }

    private function position(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::Queued => 1,
            self::Processing => 2,
            self::Succeeded, self::Failed => 3,
        };
    }
}

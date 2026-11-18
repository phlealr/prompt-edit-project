<?php

namespace App\ImageProviders;

use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use InvalidArgumentException;

/**
 * The provider's view of a generation at one moment, from a submission response, a webhook or a
 * poll (ADR 0010). A submission the provider refused, or that we did not send, is a failed update
 * with no provider id.
 */
final readonly class ProviderUpdate
{
    /**
     * @param  string|null  $rawResponse  The provider's own response, kept for audit only; null when
     *                                    there was none.
     */
    public function __construct(
        public ?string $providerId,
        public GenerationStatus $status,
        public ?string $rawResponse,
        public ?FailureReason $failureReason = null,
    ) {
        // A failure reason is set exactly when the status is failed (ADR 0025).
        if (($status === GenerationStatus::Failed) !== ($failureReason !== null)) {
            throw new InvalidArgumentException('A failure reason goes with the failed status, and only with it.');
        }
    }

    /**
     * A generation that failed before the provider created it.
     */
    public static function failedBeforeCreation(FailureReason $reason, ?string $rawResponse = null): self
    {
        return new self(null, GenerationStatus::Failed, $rawResponse, $reason);
    }
}

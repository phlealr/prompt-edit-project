<?php

namespace App\ImageProviders;

use App\Enums\GenerationStatus;

/**
 * The provider's view of a generation at one moment, from a submission response, a webhook or a
 * poll (ADR 0010).
 */
final readonly class ProviderUpdate
{
    /**
     * @param  string  $rawResponse  The provider's own response body, kept for audit only.
     */
    public function __construct(
        public string $providerId,
        public GenerationStatus $status,
        public string $rawResponse,
    ) {}
}

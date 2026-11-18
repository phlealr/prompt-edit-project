<?php

namespace App\ImageProviders;

/**
 * What a webhook says about a generation: which of ours it is, and the provider's view of it.
 */
final readonly class WebhookUpdate
{
    /**
     * @param  int|null  $generationId  Our id, from the provider's metadata; null when it has none.
     */
    public function __construct(
        public ?int $generationId,
        public ProviderUpdate $update,
    ) {}
}

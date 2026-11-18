<?php

namespace App\ImageProviders;

/**
 * A webhook the provider signed, in our terms (ADR 0010).
 */
final readonly class VerifiedWebhook
{
    /**
     * @param  string  $id  The provider's event id, unique per event and repeated on redelivery.
     * @param  bool  $isPing  A test event, about no generation.
     */
    public function __construct(
        public string $id,
        public bool $isPing,
    ) {}
}

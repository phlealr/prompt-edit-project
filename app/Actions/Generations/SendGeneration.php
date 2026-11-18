<?php

namespace App\Actions\Generations;

use App\Enums\GenerationStatus;
use App\ImageProviders\ImageProvider;
use App\Models\Generation;

class SendGeneration
{
    public function __construct(
        private ImageProvider $provider,
        private ApplyProviderUpdate $applyProviderUpdate,
    ) {}

    /**
     * Sends a pending generation to the provider, once its rates are confirmed unchanged
     * (ADR 0009, ADR 0026).
     */
    public function handle(Generation $generation): void
    {
        // Already sent: a repeated job has nothing left to do.
        if ($generation->status !== GenerationStatus::Pending) {
            return;
        }

        if (! $this->provider->currentRates()->equals($generation->rates)) {
            return;
        }

        $this->applyProviderUpdate->handle($generation, $this->provider->submit($generation));
    }
}

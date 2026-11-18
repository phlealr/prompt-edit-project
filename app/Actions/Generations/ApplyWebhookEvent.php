<?php

namespace App\Actions\Generations;

use App\ImageProviders\ImageProvider;
use App\Models\Generation;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;

class ApplyWebhookEvent
{
    public function __construct(
        private ImageProvider $provider,
        private ApplyProviderUpdate $applyProviderUpdate,
    ) {}

    /**
     * Applies a recorded webhook event to the generation named in its metadata, through the one
     * action that changes generations (ADR 0010).
     */
    public function handle(WebhookEvent $event): void
    {
        $webhook = $this->provider->updateFromWebhook($event->payload);
        $generation = $webhook->generationId === null ? null : Generation::query()->find($webhook->generationId);

        if ($generation === null) {
            Log::warning('A webhook event names no generation of ours; nothing was applied.', [
                'event' => $event->provider_event_id,
                'generation' => $webhook->generationId,
            ]);

            return;
        }

        $this->applyProviderUpdate->handle($generation, $webhook->update);
    }
}

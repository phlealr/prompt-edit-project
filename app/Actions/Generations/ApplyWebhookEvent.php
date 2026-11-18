<?php

namespace App\Actions\Generations;

use App\Enums\ImageProviderName;
use App\ImageProviders\ImageProviders;
use App\Models\Generation;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Log;

class ApplyWebhookEvent
{
    public function __construct(
        private ImageProviders $providers,
        private ApplyProviderUpdate $applyProviderUpdate,
    ) {}

    /**
     * Applies a recorded webhook event to the generation named in its metadata, through the one
     * action that changes generations (ADR 0010).
     */
    public function handle(WebhookEvent $event): void
    {
        // Read by the provider that sent it (ADR 0044).
        $webhook = $this->providers->for(ImageProviderName::from($event->provider))->updateFromWebhook($event->payload);
        $generation = $webhook->generationId === null ? null : Generation::query()->find($webhook->generationId);

        // A generation is handled only by the provider it was sent to (ADR 0044).
        if ($generation !== null && $generation->provider->value !== $event->provider) {
            $generation = null;
        }

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

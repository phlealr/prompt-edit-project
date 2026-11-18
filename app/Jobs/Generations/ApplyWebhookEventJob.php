<?php

namespace App\Jobs\Generations;

use App\Actions\Generations\ApplyWebhookEvent;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Applies a recorded webhook event to its generation, outside the webhook request (ADR 0010).
 */
class ApplyWebhookEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $webhookEventId) {}

    public function handle(ApplyWebhookEvent $applyWebhookEvent): void
    {
        $applyWebhookEvent->handle(WebhookEvent::query()->findOrFail($this->webhookEventId));
    }
}

<?php

namespace App\Actions\Generations;

use App\Enums\ImageProviderName;
use App\ImageProviders\Exceptions\InvalidWebhook;
use App\ImageProviders\ImageProviders;
use App\Jobs\Generations\ApplyWebhookEventJob;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\HeaderBag;

class RecordWebhookEvent
{
    public function __construct(private ImageProviders $providers) {}

    /**
     * Verifies a webhook, records it once by its event id, and queues the job that applies it. The
     * request does nothing else, so the provider gets its answer fast (ADR 0010).
     *
     * @throws InvalidWebhook
     */
    public function handle(ImageProviderName $provider, string $body, HeaderBag $headers): void
    {
        $webhook = $this->providers->for($provider)->verifyWebhook($body, $headers);

        // The event row and its job (on the database queue) commit together: if dispatching fails,
        // nothing is recorded, the provider gets an error and retries, and the retry dispatches.
        DB::transaction(function () use ($provider, $webhook, $body): void {
            // A redelivered event finds its row already there, and is not applied again.
            $record = WebhookEvent::query()->createOrFirst(
                ['provider' => $provider->value, 'provider_event_id' => $webhook->id],
                ['payload' => $body],
            );

            if ($record->wasRecentlyCreated && ! $webhook->isPing) {
                ApplyWebhookEventJob::dispatch($record->id);
            }
        });
    }
}

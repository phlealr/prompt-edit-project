<?php

namespace App\Jobs\Generations;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Applies a recorded webhook event to its generation (ADR 0010). Applying comes with
 * generation-updates 02.
 */
class ApplyWebhookEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $webhookEventId) {}

    public function handle(): void
    {
        //
    }
}

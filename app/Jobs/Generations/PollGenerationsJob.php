<?php

namespace App\Jobs\Generations;

use App\Actions\Generations\PollGenerations;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Every minute, asks the provider about generations whose webhooks may be lost (ADR 0027).
 */
class PollGenerationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(PollGenerations $pollGenerations): void
    {
        $pollGenerations->handle();
    }
}

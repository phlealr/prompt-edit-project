<?php

namespace App\Jobs\Generations;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends a pending generation to the provider (ADR 0009). Sending comes with ticket generating 06.
 */
class SubmitGenerationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $generationId) {}

    public function handle(): void
    {
        //
    }
}

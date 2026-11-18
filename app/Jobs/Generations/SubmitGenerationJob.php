<?php

namespace App\Jobs\Generations;

use App\Actions\Generations\SendGeneration;
use App\Models\Generation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends a pending generation to the provider, outside any web request (ADR 0009).
 */
class SubmitGenerationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $generationId) {}

    public function handle(SendGeneration $sendGeneration): void
    {
        $sendGeneration->handle(Generation::query()->findOrFail($this->generationId));
    }
}

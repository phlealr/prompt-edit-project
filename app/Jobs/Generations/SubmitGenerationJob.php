<?php

namespace App\Jobs\Generations;

use App\Actions\Generations\ApplyProviderUpdate;
use App\Actions\Generations\SendGeneration;
use App\Enums\FailureReason;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\Exceptions\ProviderBusy;
use App\ImageProviders\Exceptions\ProviderUnreachable;
use App\ImageProviders\ProviderUpdate;
use App\Models\Generation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends a pending generation to the provider, outside any web request, and waits out the provider's
 * limits (ADR 0009, ADR 0022).
 */
class SubmitGenerationJob implements ShouldQueue
{
    use Queueable;

    private const int FIRST_BACKOFF_SECONDS = 5;

    public function __construct(public int $generationId) {}

    /**
     * The maximum number of attempts, from configuration (default 8).
     */
    public function tries(): int
    {
        return (int) config('services.pixelforge.submit_max_attempts');
    }

    public function handle(SendGeneration $sendGeneration): void
    {
        try {
            $sendGeneration->handle(Generation::query()->findOrFail($this->generationId));
        } catch (ProviderBusy $exception) {
            $this->release(max($exception->retryAfterSeconds, $this->backoffSeconds()));
        } catch (ProviderUnreachable|PricingUnavailable) {
            $this->release($this->backoffSeconds());
        }
    }

    /**
     * Out of attempts: the generation fails with provider_unavailable and its hold is released,
     * unless the provider may have created it; then it stays pending for polling to re-send
     * (ADR 0022, ADR 0042).
     */
    public function failed(?Throwable $exception): void
    {
        $generation = Generation::query()->findOrFail($this->generationId);

        if ($generation->may_exist_at_provider) {
            Log::error('The submission job gave up on a generation the provider may have created; polling will re-send it.', [
                'generation' => $generation->id,
                'last_error' => $exception?->getMessage(),
            ]);

            return;
        }

        app(ApplyProviderUpdate::class)->handle(
            $generation,
            ProviderUpdate::failedBeforeCreation(FailureReason::ProviderUnavailable, $exception?->getMessage()),
        );
    }

    /**
     * 5 seconds after the first attempt, doubling each time, at most the configured cap.
     */
    private function backoffSeconds(): int
    {
        $backoff = self::FIRST_BACKOFF_SECONDS * 2 ** ($this->attempts() - 1);

        return min($backoff, (int) config('services.pixelforge.submit_backoff_cap_seconds'));
    }
}

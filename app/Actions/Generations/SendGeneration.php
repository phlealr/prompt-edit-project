<?php

namespace App\Actions\Generations;

use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\ImageProviders\Exceptions\PromptRejected;
use App\ImageProviders\Exceptions\SubmissionRefused;
use App\ImageProviders\ImageProvider;
use App\ImageProviders\ProviderUpdate;
use App\Models\Generation;
use Illuminate\Support\Facades\Log;

class SendGeneration
{
    public function __construct(
        private ImageProvider $provider,
        private ApplyProviderUpdate $applyProviderUpdate,
    ) {}

    /**
     * Sends a pending generation to the provider, once its rates are confirmed unchanged, and fails
     * it at once when retrying cannot help (ADR 0009, ADR 0022, ADR 0026).
     */
    public function handle(Generation $generation): void
    {
        // Already sent or failed: a repeated job has nothing left to do.
        if ($generation->status !== GenerationStatus::Pending) {
            return;
        }

        if (! $this->provider->currentRates()->equals($generation->rates)) {
            $this->fail($generation, FailureReason::PriceChanged);

            return;
        }

        try {
            $update = $this->provider->submit($generation);
        } catch (PromptRejected $exception) {
            $this->fail($generation, FailureReason::PromptRejected, $exception->getMessage());

            return;
        } catch (SubmissionRefused $exception) {
            Log::error('The provider refused a generation; it fails without retrying.', [
                'generation' => $generation->id,
                'answer' => $exception->getMessage(),
            ]);
            $this->fail($generation, FailureReason::ProviderUnavailable, $exception->getMessage());

            return;
        }

        $this->applyProviderUpdate->handle($generation, $update);
    }

    private function fail(Generation $generation, FailureReason $reason, ?string $providerAnswer = null): void
    {
        $this->applyProviderUpdate->handle($generation, ProviderUpdate::failedBeforeCreation($reason, $providerAnswer));
    }
}

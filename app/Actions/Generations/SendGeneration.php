<?php

namespace App\Actions\Generations;

use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\Exceptions\PromptRejected;
use App\ImageProviders\Exceptions\ProviderBusy;
use App\ImageProviders\Exceptions\ProviderUnreachable;
use App\ImageProviders\Exceptions\SubmissionRefused;
use App\ImageProviders\ImageProviders;
use App\ImageProviders\ProviderUpdate;
use App\Models\Generation;
use Illuminate\Support\Facades\Log;

class SendGeneration
{
    public function __construct(
        private ImageProviders $providers,
        private ApplyProviderUpdate $applyProviderUpdate,
    ) {}

    /**
     * Sends a pending generation to the provider, once its rates are confirmed unchanged, and fails
     * it at once when retrying cannot help (ADR 0009, ADR 0022, ADR 0026). A busy or unreachable
     * provider is thrown on to the job, which retries.
     *
     * @throws ProviderBusy
     * @throws ProviderUnreachable
     * @throws PricingUnavailable
     */
    public function handle(Generation $generation): void
    {
        // Already sent or failed: a repeated job has nothing left to do.
        if ($generation->status !== GenerationStatus::Pending) {
            return;
        }

        // The provider this generation was recorded with, even if new ones now go elsewhere.
        $provider = $this->providers->for($generation->provider);

        // Once an attempt may have reached the provider, it may be creating the generation, so it
        // is never refused over a price change (ADR 0026, ADR 0040).
        if (! $generation->may_exist_at_provider && ! $provider->currentRates()->equals($generation->rates)) {
            $this->fail($generation, FailureReason::PriceChanged);

            return;
        }

        try {
            $update = $provider->submit($generation);
        } catch (PromptRejected $exception) {
            $this->fail($generation, FailureReason::PromptRejected, $exception->getMessage());

            return;
        } catch (ProviderUnreachable $exception) {
            $generation->update(['may_exist_at_provider' => true]);

            throw $exception;
        } catch (SubmissionRefused $exception) {
            // An earlier attempt may have created it: only a generation the provider certainly
            // does not have is failed (ADR 0042). This one is retried instead.
            if ($generation->may_exist_at_provider) {
                throw new ProviderUnreachable($exception->getMessage(), previous: $exception);
            }

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

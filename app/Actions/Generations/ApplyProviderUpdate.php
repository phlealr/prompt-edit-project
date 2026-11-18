<?php

namespace App\Actions\Generations;

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\ImageProviders\ProviderUpdate;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyProviderUpdate
{
    /**
     * The one place that changes a generation's status and writes its release and charge, whether
     * the update came from the submission, a webhook or a poll (ADR 0010). Statuses only move
     * forward, so a late, repeated or out-of-order update changes nothing (ADR 0025).
     */
    public function handle(Generation $generation, ProviderUpdate $update): void
    {
        DB::transaction(function () use ($generation, $update): void {
            // Locking the generation makes two updates arriving together apply one after the other.
            $current = Generation::query()->whereKey($generation->id)->lockForUpdate()->firstOrFail();

            if (! $current->status->canMoveTo($update->status)) {
                return;
            }

            // An update about another provider generation than the one we stored is not ours.
            if ($current->provider_id !== null && $update->providerId !== null && $current->provider_id !== $update->providerId) {
                Log::warning('A provider update names another provider generation; nothing was applied.', [
                    'generation' => $current->id,
                    'stored_provider_id' => $current->provider_id,
                    'update_provider_id' => $update->providerId,
                ]);

                return;
            }

            // A failure decided before the provider created anything only applies while the
            // generation is still pending: once the provider has it, only the provider ends it.
            if ($update->providerId === null && $current->status !== GenerationStatus::Pending) {
                return;
            }

            $current->update([
                'status' => $update->status,
                'failure_reason' => $update->failureReason,
                'provider_id' => $update->providerId,
                'provider_response' => $update->rawResponse,
                'provider_cost' => $update->providerCost,
                'image_url' => $update->imageUrl,
                'image_url_expires_at' => $update->imageUrlExpiresAt,
                'started_at' => $update->startedAt,
                'completed_at' => $update->completedAt,
            ]);

            // A failed generation costs nothing: its hold comes back as a release (ADR 0006).
            if ($update->status === GenerationStatus::Failed) {
                $this->releaseHold($current);
            }

            // A succeeded one gives its hold back and is charged the exact amount, in full. The
            // provider cost is set exactly when the update is succeeded (ProviderUpdate).
            if ($update->providerCost !== null) {
                $held = $this->releaseHold($current);
                $this->charge($current, $update->providerCost, $held);
            }
        });
    }

    /**
     * @return Money The amount that was held, as a positive amount.
     */
    private function releaseHold(Generation $generation): Money
    {
        $hold = CreditTransaction::query()
            ->where('generation_id', $generation->id)
            ->where('type', CreditTransactionType::Hold)
            ->sole();
        $held = $hold->amount->negate();

        CreditTransaction::query()->create([
            'user_id' => $generation->user_id,
            'generation_id' => $generation->id,
            'type' => CreditTransactionType::Release,
            'amount' => $held,
        ]);

        return $held;
    }

    /**
     * Charges the cost plus the generation's own markup, rounded up, in full: never capped at the
     * hold (ADR 0005, ADR 0026, ADR 0045).
     */
    private function charge(Generation $generation, Money $providerCost, Money $held): void
    {
        $charge = $providerCost->plusMarkup($generation->markup_percent);

        CreditTransaction::query()->create([
            'user_id' => $generation->user_id,
            'generation_id' => $generation->id,
            'type' => CreditTransactionType::Charge,
            'amount' => $charge->negate(),
        ]);

        if ($charge->nanodollars() > $held->nanodollars()) {
            Log::warning('A charge exceeds its hold; the difference is uncovered.', [
                'generation' => $generation->id,
                'hold' => $held->exact(),
                'charge' => $charge->exact(),
                'difference' => Money::fromNanodollars($charge->nanodollars() - $held->nanodollars())->exact(),
            ]);
        }
    }
}

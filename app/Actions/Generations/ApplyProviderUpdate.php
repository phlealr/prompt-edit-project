<?php

namespace App\Actions\Generations;

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\ImageProviders\ProviderUpdate;
use App\Models\CreditTransaction;
use App\Models\Generation;
use Illuminate\Support\Facades\DB;

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

            // A failure decided before the provider created anything only applies while the
            // generation is still pending: once the provider has it, only the provider ends it.
            if ($update->providerId === null && $current->status !== GenerationStatus::Pending) {
                return;
            }

            // Success settles the hold with a charge, which generation-updates 03 adds. Until then a
            // later webhook or poll settles it.
            if ($update->status === GenerationStatus::Succeeded) {
                return;
            }

            $current->update([
                'status' => $update->status,
                'failure_reason' => $update->failureReason,
                'provider_id' => $update->providerId,
                'provider_response' => $update->rawResponse,
            ]);

            // A failed generation costs nothing: its hold comes back as a release (ADR 0006).
            if ($update->status === GenerationStatus::Failed) {
                $this->releaseHold($current);
            }
        });
    }

    private function releaseHold(Generation $generation): void
    {
        $hold = CreditTransaction::query()
            ->where('generation_id', $generation->id)
            ->where('type', CreditTransactionType::Hold)
            ->sole();

        CreditTransaction::query()->create([
            'user_id' => $generation->user_id,
            'generation_id' => $generation->id,
            'type' => CreditTransactionType::Release,
            'amount' => $hold->amount->negate(),
        ]);
    }
}

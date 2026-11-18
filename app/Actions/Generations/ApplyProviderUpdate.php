<?php

namespace App\Actions\Generations;

use App\Enums\GenerationStatus;
use App\ImageProviders\ProviderUpdate;
use App\Models\Generation;
use Illuminate\Support\Facades\DB;

class ApplyProviderUpdate
{
    /**
     * The one place that changes a generation from the provider's view of it, whether that came
     * from the submission response, a webhook or a poll (ADR 0010). Statuses only move forward, so
     * a late, repeated or out-of-order update changes nothing (ADR 0025).
     */
    public function handle(Generation $generation, ProviderUpdate $update): void
    {
        DB::transaction(function () use ($generation, $update): void {
            // Locking the generation makes two updates arriving together apply one after the other.
            $current = Generation::query()->whereKey($generation->id)->lockForUpdate()->firstOrFail();

            if (! $current->status->canMoveTo($update->status)) {
                return;
            }

            // A final status settles the hold (release, and a charge on success), which the
            // generation-updates tickets add. Until then a later webhook or poll settles it.
            if ($update->status === GenerationStatus::Succeeded || $update->status === GenerationStatus::Failed) {
                return;
            }

            $current->update([
                'status' => $update->status,
                'provider_id' => $update->providerId,
                'provider_response' => $update->rawResponse,
            ]);
        });
    }
}

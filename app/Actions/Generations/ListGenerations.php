<?php

namespace App\Actions\Generations;

use App\Data\Generations\GenerationSummary;
use App\Models\Generation;
use App\Models\User;

class ListGenerations
{
    /**
     * The user's own generations, newest first, as the history page shows them.
     *
     * @return array<int, GenerationSummary>
     */
    public function handle(User $user): array
    {
        return Generation::query()
            ->whereBelongsTo($user)
            ->latest()
            ->latest('id')
            ->get()
            ->map(fn (Generation $generation): GenerationSummary => GenerationSummary::from($generation))
            ->all();
    }
}

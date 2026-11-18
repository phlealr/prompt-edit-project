<?php

namespace App\Actions\Generations;

use App\Models\Generation;
use App\Models\User;

class ListGenerations
{
    /**
     * The user's own generations, newest first, as the history page shows them.
     *
     * @return array<int, array{id: int, prompt: string, status: string}>
     */
    public function handle(User $user): array
    {
        return Generation::query()
            ->whereBelongsTo($user)
            ->latest()
            ->latest('id')
            ->get()
            ->map(fn (Generation $generation): array => [
                'id' => $generation->id,
                'prompt' => $generation->prompt,
                'status' => $generation->status->value,
            ])
            ->all();
    }
}

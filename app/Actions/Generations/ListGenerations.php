<?php

namespace App\Actions\Generations;

use App\Data\Generations\GenerationCard;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListGenerations
{
    private const int PER_PAGE = 20;

    /**
     * The user's own generations, newest first, 20 per page, as history cards.
     *
     * @return LengthAwarePaginator<int, GenerationCard>
     */
    public function handle(User $user): LengthAwarePaginator
    {
        return Generation::query()
            ->whereBelongsTo($user)
            ->with('charge')
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->through(fn (Generation $generation): GenerationCard => GenerationCard::from($generation));
    }
}

<?php

namespace App\Actions\Generations;

use App\Actions\Credits\CalculateBalance;
use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\Exceptions\NotEnoughCredits;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\ImageProvider;
use App\Jobs\Generations\SubmitGenerationJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmitGeneration
{
    public function __construct(
        private ImageProvider $provider,
        private CalculateBalance $calculateBalance,
    ) {}

    /**
     * Holds the estimate's maximum and queues the generation for the provider (ADR 0006, ADR 0009).
     *
     * @param  list<string>  $referenceImages
     *
     * @throws PricingUnavailable
     * @throws NotEnoughCredits
     */
    public function handle(User $user, string $prompt, Size $size, Quality $quality, array $referenceImages): Generation
    {
        $estimate = $this->provider->estimate($prompt, $size, $quality, $referenceImages);

        return DB::transaction(function () use ($user, $prompt, $size, $quality, $referenceImages, $estimate): Generation {
            // Locking the user's row makes a second submission wait here until this one commits, so
            // both cannot spend the same balance.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $balance = $this->calculateBalance->handle($user);

            if ($balance->nanodollars() < $estimate->maximum->nanodollars()) {
                throw new NotEnoughCredits($estimate->maximum, $balance);
            }

            $generation = Generation::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'prompt' => $prompt,
                'size' => $size,
                'quality' => $quality,
                'reference_images' => $referenceImages,
                'status' => GenerationStatus::Pending,
                'rates' => $estimate->rates,
                'estimate_minimum' => $estimate->minimum,
                'estimate_maximum' => $estimate->maximum,
            ]);

            CreditTransaction::query()->create([
                'user_id' => $user->id,
                'generation_id' => $generation->id,
                'type' => CreditTransactionType::Hold,
                'amount' => $estimate->maximum->negate(),
            ]);

            // Dispatched inside the transaction: with the database queue, the job is saved or
            // rolled back together with the generation and its hold (ADR 0004, ADR 0009).
            SubmitGenerationJob::dispatch($generation->id);

            return $generation;
        });
    }
}

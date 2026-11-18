<?php

namespace App\Actions\Generations;

use App\Enums\GenerationStatus;
use App\ImageProviders\Exceptions\GenerationNotFound;
use App\ImageProviders\Exceptions\ProviderBusy;
use App\ImageProviders\Exceptions\ProviderUnreachable;
use App\ImageProviders\ImageProviders;
use App\ImageProviders\ProviderUpdate;
use App\Models\Generation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class PollGenerations
{
    /** A generation still not final this long after submission is logged as an error (ADR 0027). */
    private const int STUCK_AFTER_MINUTES = 60;

    public function __construct(
        private ImageProviders $providers,
        private ApplyProviderUpdate $applyProviderUpdate,
        private SendGeneration $sendGeneration,
    ) {}

    /**
     * Asks the provider about generations whose webhooks may be lost, and applies the answers
     * through the one action (ADR 0010, ADR 0027). Also re-sends generations the submission job
     * gave up on while they may exist at the provider (ADR 0042). Never fails a generation itself.
     */
    public function handle(): void
    {
        foreach ($this->dueGenerations() as $generation) {
            // Marked first, so a generation that keeps failing goes to the back of the line.
            $this->markPolled($generation);

            try {
                $this->poll($generation);
            } catch (ProviderBusy) {
                // Rate limited: the rest wait for the next run.
                return;
            } catch (ProviderUnreachable) {
                // This one waits for the next run; the others go on.
                continue;
            } catch (GenerationNotFound $exception) {
                Log::error('The provider does not know a generation we are waiting on; it keeps its hold.', [
                    'generation' => $generation->id,
                    'answer' => $exception->getMessage(),
                ]);
            } catch (Throwable $exception) {
                // Anything unexpected about one generation must not stop the others being polled.
                Log::error('Polling a generation failed unexpectedly; it keeps its hold.', [
                    'generation' => $generation->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * A pending generation the submission job gave up on is re-sent with its idempotency key,
     * which returns it if the provider created it (ADR 0042); any other is fetched.
     */
    private function poll(Generation $generation): void
    {
        if ($generation->status === GenerationStatus::Pending) {
            $this->sendGeneration->handle($generation);

            return;
        }

        $update = $this->providers->for($generation->provider)->fetch($generation);
        $this->applyProviderUpdate->handle($generation, $update);
        $this->logIfStuck($update, $generation);
    }

    /**
     * Queued and processing generations not updated or polled within the configured age, plus the
     * pending ones the submission job gave up on and not polled within it, least recently updated
     * or polled first, at most the configured number.
     *
     * @return Collection<int, Generation>
     */
    private function dueGenerations(): Collection
    {
        $quietSince = now()->subSeconds((int) config('services.pixelforge.poll_after_seconds'));

        return Generation::query()
            ->where(fn ($query) => $query
                ->whereIn('status', [GenerationStatus::Queued, GenerationStatus::Processing])
                ->where('updated_at', '<', $quietSince)
                ->where(fn ($query) => $query->whereNull('polled_at')->orWhere('polled_at', '<', $quietSince)))
            // The submission job already waited out its own backoff on these.
            ->orWhere(fn ($query) => $query
                ->where('status', GenerationStatus::Pending)
                ->whereNotNull('submission_gave_up_at')
                ->where(fn ($query) => $query->whereNull('polled_at')->orWhere('polled_at', '<', $quietSince)))
            ->get()
            // Few generations are ever waiting (the provider runs 3 at once), so sorting here keeps
            // the SQL portable.
            ->sortBy(fn (Generation $generation) => max($generation->updated_at, $generation->polled_at ?? $generation->updated_at))
            ->take((int) config('services.pixelforge.poll_max_per_run'))
            ->values();
    }

    /**
     * Records the poll without touching updated_at, which stays the time the generation last changed.
     */
    private function markPolled(Generation $generation): void
    {
        Generation::query()->whereKey($generation->id)->toBase()->update(['polled_at' => now()]);
    }

    private function logIfStuck(ProviderUpdate $update, Generation $generation): void
    {
        $isFinal = $update->status === GenerationStatus::Succeeded || $update->status === GenerationStatus::Failed;

        if (! $isFinal && $generation->created_at->lt(now()->subMinutes(self::STUCK_AFTER_MINUTES))) {
            Log::error('A generation is still not final an hour after submission; it keeps its hold.', [
                'generation' => $generation->id,
                'status' => $update->status->value,
            ]);
        }
    }
}

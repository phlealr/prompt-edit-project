<?php

namespace Tests\Fakes;

use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Estimate;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\ImageProvider;
use App\ImageProviders\ProviderUpdate;
use App\ImageProviders\Rates;
use App\Models\Generation;
use App\ValueObjects\Money;
use Throwable;

/**
 * An ImageProvider for tests above the provider boundary: it answers from its properties and never
 * reaches the network. Bind it with fakeImageProvider() from Pest.php.
 */
final class FakeImageProvider implements ImageProvider
{
    public Estimate $estimate;

    public bool $pricingUnavailable = false;

    /** The rates currentRates() answers; by default the estimate's own. */
    public Rates $currentRates;

    public int $currentRatesCalls = 0;

    /** The status submit() answers with. */
    public GenerationStatus $submitStatus = GenerationStatus::Queued;

    /**
     * Thrown by the next submit() calls, one per call, before it answers normally.
     *
     * @var list<Throwable>
     */
    public array $submitFailures = [];

    /** @var list<string> The idempotency keys of every submit() call. */
    public array $submitted = [];

    /** The input of the last estimate() call. */
    public ?string $estimatedPrompt = null;

    public ?Size $estimatedSize = null;

    public ?Quality $estimatedQuality = null;

    /** @var list<string>|null */
    public ?array $estimatedReferenceImages = null;

    public function __construct()
    {
        $this->estimate = new Estimate(
            Money::fromNanodollars(10_000_000),
            Money::fromNanodollars(20_000_000),
            new Rates(
                Money::fromDecimalString('5.00'),
                Money::fromDecimalString('10.00'),
                Money::fromDecimalString('40.00'),
                Money::fromDecimalString('12.50'),
            ),
        );
        $this->currentRates = $this->estimate->rates;
    }

    public function estimate(string $prompt, Size $size, Quality $quality, array $referenceImages): Estimate
    {
        if ($this->pricingUnavailable) {
            throw new PricingUnavailable('Fake pricing is unavailable.');
        }

        $this->estimatedPrompt = $prompt;
        $this->estimatedSize = $size;
        $this->estimatedQuality = $quality;
        $this->estimatedReferenceImages = $referenceImages;

        return $this->estimate;
    }

    public function currentRates(): Rates
    {
        $this->currentRatesCalls++;

        if ($this->pricingUnavailable) {
            throw new PricingUnavailable('Fake pricing is unavailable.');
        }

        return $this->currentRates;
    }

    public function submit(Generation $generation): ProviderUpdate
    {
        $this->submitted[] = $generation->uuid;

        $failure = array_shift($this->submitFailures);

        if ($failure !== null) {
            throw $failure;
        }

        return new ProviderUpdate("gen_fake_{$generation->id}", $this->submitStatus, '{"fake":true}');
    }
}

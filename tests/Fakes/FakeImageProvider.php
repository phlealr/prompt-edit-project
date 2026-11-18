<?php

namespace Tests\Fakes;

use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Estimate;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\ImageProvider;
use App\ImageProviders\Pixelforge\PixelforgeImageProvider;
use App\ImageProviders\ProviderUpdate;
use App\ImageProviders\Rates;
use App\ImageProviders\VerifiedWebhook;
use App\ImageProviders\WebhookUpdate;
use App\Models\Generation;
use App\ValueObjects\Money;
use Symfony\Component\HttpFoundation\HeaderBag;
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

    /** The status fetch() answers with. */
    public GenerationStatus $fetchStatus = GenerationStatus::Processing;

    /**
     * Thrown by the next fetch() calls, one per call, before it answers normally.
     *
     * @var list<Throwable>
     */
    public array $fetchFailures = [];

    /** @var list<string> The provider ids of every fetch() call. */
    public array $fetched = [];

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

    /**
     * Trusts the body: webhook tests sign their requests and go through the Pixelforge
     * implementation instead.
     */
    public function verifyWebhook(string $body, HeaderBag $headers): VerifiedWebhook
    {
        $event = json_decode($body);

        return new VerifiedWebhook($event->id, $event->type === 'ping');
    }

    /**
     * Webhook tests go through the Pixelforge implementation; the fake reads the same body.
     */
    public function updateFromWebhook(string $payload): WebhookUpdate
    {
        return app(PixelforgeImageProvider::class)->updateFromWebhook($payload);
    }

    public function fetch(Generation $generation): ProviderUpdate
    {
        $this->fetched[] = (string) $generation->provider_id;
        $failure = array_shift($this->fetchFailures);

        if ($failure !== null) {
            throw $failure;
        }

        return new ProviderUpdate($generation->provider_id, $this->fetchStatus, '{"fake":true}');
    }
}

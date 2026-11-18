<?php

namespace App\ImageProviders;

use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Exceptions\GenerationNotFound;
use App\ImageProviders\Exceptions\InvalidWebhook;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\Exceptions\ProviderBusy;
use App\ImageProviders\Exceptions\ProviderUnreachable;
use App\ImageProviders\Exceptions\UsageUnavailable;
use App\Models\Generation;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * An external image-generation service (ADR 0014).
 */
interface ImageProvider
{
    /**
     * @param  list<string>  $referenceImages  Their URLs; a URL given twice is billed twice.
     *
     * @throws PricingUnavailable
     */
    public function estimate(string $prompt, Size $size, Quality $quality, array $referenceImages): Estimate;

    /**
     * The provider's rates right now, never from a cache (ADR 0026).
     *
     * @throws PricingUnavailable
     */
    public function currentRates(): Rates;

    /**
     * Sends the generation to the provider, with its UUID as idempotency key and its id in the
     * metadata (ADR 0009). Sending it again returns the same generation as it is now.
     */
    public function submit(Generation $generation): ProviderUpdate;

    /**
     * The generation as the provider sees it now, by the provider's id (ADR 0027).
     *
     * @throws GenerationNotFound
     * @throws ProviderBusy
     * @throws ProviderUnreachable
     */
    public function fetch(Generation $generation): ProviderUpdate;

    /**
     * What the provider billed per UTC day, for any range of days (ADR 0016, ADR 0030).
     *
     * @throws UsageUnavailable
     */
    public function usage(CarbonImmutable $start, CarbonImmutable $end): ProviderUsage;

    /**
     * Checks that a webhook request comes from the provider, on its raw body, and reads which event
     * it is.
     *
     * @throws InvalidWebhook
     */
    public function verifyWebhook(string $body, HeaderBag $headers): VerifiedWebhook;

    /**
     * Turns a verified webhook's body into which generation of ours it is about, and the provider's
     * view of it.
     */
    public function updateFromWebhook(string $payload): WebhookUpdate;
}

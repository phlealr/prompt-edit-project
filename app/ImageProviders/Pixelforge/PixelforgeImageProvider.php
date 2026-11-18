<?php

namespace App\ImageProviders\Pixelforge;

use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Estimate;
use App\ImageProviders\Exceptions\InvalidWebhook;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\Exceptions\PromptRejected;
use App\ImageProviders\Exceptions\ProviderBusy;
use App\ImageProviders\Exceptions\ProviderUnreachable;
use App\ImageProviders\Exceptions\SubmissionRefused;
use App\ImageProviders\ImageProvider;
use App\ImageProviders\ProviderUpdate;
use App\ImageProviders\Rates;
use App\ImageProviders\VerifiedWebhook;
use App\ImageProviders\WebhookUpdate;
use App\Models\Generation;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use stdClass;
use Symfony\Component\HttpFoundation\HeaderBag;
use TypeError;
use UnexpectedValueException;

final class PixelforgeImageProvider implements ImageProvider
{
    /** Rates are in dollars per 1M tokens. */
    private const int TOKENS_PER_RATE = 1_000_000;

    /** Webhooks signed more than 5 minutes before or after now are refused, to stop replays. */
    private const int WEBHOOK_TOLERANCE_SECONDS = 300;

    private const array RATE_TYPES = ['input_text_tokens', 'input_image_tokens', 'output_tokens', 'reasoning_tokens'];

    public function estimate(string $prompt, Size $size, Quality $quality, array $referenceImages): Estimate
    {
        $pricing = $this->pricing();

        // One text token per `charactersPerTextToken` Unicode characters, rounded up, then −15%
        // rounded down and +15% rounded up (ADR 0024; the pricing rules check the 15%).
        $textTokens = $this->ceilDiv(mb_strlen($prompt), $pricing->charactersPerTextToken);
        $imageTokens = $pricing->tokensPerReferenceImage * count($referenceImages);
        $outputTokens = $pricing->outputTokens($quality, $size);
        $reasoningTokens = $pricing->reasoningTokens($quality);

        $minimumCost = $this->providerCost($pricing->rates, intdiv($textTokens * 85, 100), $imageTokens, $outputTokens, $reasoningTokens->minimum);
        $maximumCost = $this->providerCost($pricing->rates, $this->ceilDiv($textTokens * 115, 100), $imageTokens, $outputTokens, $reasoningTokens->maximum);

        return new Estimate($minimumCost->plusMarkup(), $maximumCost->plusMarkup(), $pricing->rates);
    }

    public function currentRates(): Rates
    {
        return $this->fetchPricing()->rates;
    }

    public function submit(Generation $generation): ProviderUpdate
    {
        try {
            $response = $this->http()
                ->withToken((string) config('services.pixelforge.api_key'))
                ->withHeaders(['Idempotency-Key' => $generation->uuid])
                ->post('generations', [
                    'prompt' => $generation->prompt,
                    'size' => $generation->size->value,
                    'quality' => $generation->quality->value,
                    'reference_images' => $generation->reference_images,
                    'metadata' => ['generation_id' => (string) $generation->id],
                ])
                ->throw(fn (Response $response) => $this->throwSubmissionError($response));
        } catch (ConnectionException $exception) {
            throw new ProviderUnreachable($exception->getMessage(), previous: $exception);
        }

        // An answer we cannot read still means the provider may have created the generation.
        try {
            return $this->providerUpdateFrom(json_decode($response->body()), $response->body());
        } catch (UnexpectedValueException|TypeError $exception) {
            throw new ProviderUnreachable("Pixelforge's answer could not be read: {$response->body()}", previous: $exception);
        }
    }

    /**
     * Pixelforge signs `<timestamp>.<raw body>` with HMAC-SHA256 and the key's signing secret, and
     * sends `t=<timestamp>,v1=<signature>` (docs/spec/pixelforge-api.md#verifying-signatures).
     */
    public function verifyWebhook(string $body, HeaderBag $headers): VerifiedWebhook
    {
        $timestamp = null;
        $signature = null;

        foreach (explode(',', (string) $headers->get('Pixelforge-Signature')) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');

            match ($key) {
                't' => $timestamp = (int) $value,
                'v1' => $signature = $value,
                default => null,
            };
        }

        if ($timestamp === null || $signature === null) {
            throw new InvalidWebhook('The webhook has no Pixelforge signature.');
        }

        $secret = (string) config('services.pixelforge.webhook_secret');

        // With no secret, anyone could sign with an empty key: refuse every webhook instead.
        if ($secret === '') {
            throw new InvalidWebhook('No Pixelforge webhook secret is configured.');
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);

        // hash_equals compares in constant time, so the comparison leaks nothing about the secret.
        if (! hash_equals($expected, $signature)) {
            throw new InvalidWebhook('The webhook signature does not match its body.');
        }

        if (abs(now()->getTimestamp() - $timestamp) > self::WEBHOOK_TOLERANCE_SECONDS) {
            throw new InvalidWebhook('The webhook timestamp is more than 5 minutes off.');
        }

        $event = json_decode($body);

        if (! is_object($event) || ! is_string($event->id ?? null) || ! is_string($event->type ?? null)) {
            throw new InvalidWebhook('The webhook body is not a Pixelforge event.');
        }

        return new VerifiedWebhook($event->id, $event->type === 'ping');
    }

    public function updateFromWebhook(string $payload): WebhookUpdate
    {
        // The event's data.object is the full generation object, with our id in its metadata.
        $generation = json_decode($payload)->data->object;
        $generationId = $generation->metadata->generation_id ?? null;

        return new WebhookUpdate(
            is_string($generationId) && ctype_digit($generationId) ? (int) $generationId : null,
            $this->providerUpdateFrom($generation, $payload),
        );
    }

    /**
     * Maps a refused create request to our exceptions (docs/spec/pixelforge-api.md#errors, Rate
     * limits and quotas). Any other answer stays Laravel's RequestException.
     *
     * @throws PromptRejected
     * @throws SubmissionRefused
     * @throws ProviderBusy
     * @throws ProviderUnreachable
     */
    private function throwSubmissionError(Response $response): void
    {
        $code = (string) $response->json('error.code');
        $answer = "Pixelforge answered {$response->status()} {$code}: {$response->body()}";

        if ($response->status() === 400 && $code === 'content_policy_violation') {
            throw new PromptRejected($answer);
        }

        if ($code === 'daily_limit_exceeded' || in_array($response->status(), [401, 409, 422], true)) {
            throw new SubmissionRefused($answer);
        }

        if (in_array($code, ['rate_limit_exceeded', 'concurrency_limit_exceeded', 'overloaded'], true)) {
            throw new ProviderBusy((int) $response->header('Retry-After'), $answer);
        }

        // Any other server error may have come after the generation was created (ADR 0043).
        if ($response->serverError()) {
            throw new ProviderUnreachable($answer);
        }
    }

    /**
     * Maps a Pixelforge generation object to our provider update, keeping the raw response.
     */
    private function providerUpdateFrom(stdClass $generation, string $rawResponse): ProviderUpdate
    {
        $status = match ($generation->status ?? null) {
            'queued' => GenerationStatus::Queued,
            'processing' => GenerationStatus::Processing,
            'succeeded' => GenerationStatus::Succeeded,
            'failed' => GenerationStatus::Failed,
            default => throw new UnexpectedValueException("Pixelforge sent an unknown generation status: {$rawResponse}"),
        };

        return new ProviderUpdate(
            providerId: (string) $generation->id,
            status: $status,
            rawResponse: $rawResponse,
            // Both of Pixelforge's failure codes mean it accepted the generation, then failed (ADR 0025).
            failureReason: $status === GenerationStatus::Failed ? FailureReason::ProviderFailed : null,
            providerCost: $status === GenerationStatus::Succeeded ? $this->billedCost($generation) : null,
            imageUrl: $generation->image_url ?? null,
            imageUrlExpiresAt: $this->time($generation->image_url_expires_at ?? null),
            startedAt: $this->time($generation->started_at ?? null),
            completedAt: $this->time($generation->completed_at ?? null),
        );
    }

    /**
     * What Pixelforge bills a succeeded generation: its usage at the rates on the generation itself,
     * fixed when it was created, not the current pricing (docs/spec/pixelforge-api.md#the-generation-object).
     */
    private function billedCost(stdClass $generation): Money
    {
        $usage = $generation->usage ?? null;
        $rates = $generation->rates ?? null;

        if (! $usage instanceof stdClass || ! $rates instanceof stdClass) {
            throw new UnexpectedValueException('Pixelforge sent a succeeded generation without its usage and rates.');
        }

        return $this->providerCost(
            new Rates(
                Money::fromDecimalString($rates->input_text_tokens),
                Money::fromDecimalString($rates->input_image_tokens),
                Money::fromDecimalString($rates->output_tokens),
                Money::fromDecimalString($rates->reasoning_tokens),
            ),
            $usage->input_text_tokens,
            $usage->input_image_tokens,
            $usage->output_tokens,
            $usage->reasoning_tokens,
        );
    }

    private function time(?string $iso8601): ?CarbonImmutable
    {
        // Stored in UTC: the completion time decides the UTC day the provider bills on (ADR 0016).
        return $iso8601 === null ? null : CarbonImmutable::parse($iso8601)->utc();
    }

    /**
     * The current pricing, cached for the configured time (ADR 0011, ADR 0032). A failed or
     * unusable response throws, so it is never cached.
     */
    private function pricing(): Pricing
    {
        return Cache::remember(
            'pixelforge.pricing',
            (int) config('services.pixelforge.pricing_cache_seconds'),
            fn (): Pricing => $this->fetchPricing(),
        );
    }

    private function fetchPricing(): Pricing
    {
        try {
            $pricing = $this->http()->get('pricing')->throw()->json() ?? [];
        } catch (ConnectionException|RequestException $exception) {
            throw new PricingUnavailable('Pixelforge pricing could not be fetched.', previous: $exception);
        }

        if (! is_array($pricing) || Validator::make($pricing, $this->pricingRules())->fails()) {
            throw new PricingUnavailable('Pixelforge pricing has an unexpected shape.');
        }

        /** @var array<string, mixed> $pricing */
        return Pricing::fromResponse($pricing);
    }

    /**
     * Everything estimate() reads from the pricing response, as validation rules.
     *
     * @return array<string, string|list<string>>
     */
    private function pricingRules(): array
    {
        // Upper bounds keep every product in the cost calculation far inside a 64-bit integer.
        $tokenCount = 'required|integer|min:0|max:1000000';

        $rules = [
            'tokens.characters_per_text_token' => 'required|integer|min:1|max:100',
            'tokens.text_token_variance' => 'required|in:0.15',
            'tokens.per_reference_image' => $tokenCount,
        ];

        foreach (self::RATE_TYPES as $type) {
            $rules["rates.{$type}"] = ['required', 'string', 'regex:/^\d{1,6}(\.\d{1,9})?\z/'];
        }

        foreach (Quality::cases() as $quality) {
            foreach (Size::cases() as $size) {
                $rules["tokens.output.{$quality->value}.{$size->value}"] = $tokenCount;
            }

            $rules["tokens.reasoning.{$quality->value}.min"] = $tokenCount;
            $rules["tokens.reasoning.{$quality->value}.max"] = $tokenCount;
        }

        return $rules;
    }

    /**
     * The provider cost of these token counts: Σ count × rate per 1M tokens, divided once by 1M.
     * The published rates (2 decimals) divide exactly; a rate with more than 3 decimals would leave
     * a remainder, which rounds up.
     */
    private function providerCost(Rates $rates, int $textTokens, int $imageTokens, int $outputTokens, int $reasoningTokens): Money
    {
        $nanodollarsTimesMillionTokens = $textTokens * $rates->inputTextTokens->nanodollars()
            + $imageTokens * $rates->inputImageTokens->nanodollars()
            + $outputTokens * $rates->outputTokens->nanodollars()
            + $reasoningTokens * $rates->reasoningTokens->nanodollars();

        return Money::fromNanodollars($this->ceilDiv($nanodollarsTimesMillionTokens, self::TOKENS_PER_RATE));
    }

    /**
     * $dividend / $divisor, rounded up, for non-negative integers.
     */
    private function ceilDiv(int $dividend, int $divisor): int
    {
        $quotient = intdiv($dividend, $divisor);

        if ($dividend % $divisor !== 0) {
            $quotient++;
        }

        return $quotient;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) config('services.pixelforge.base_url'))
            ->connectTimeout((int) config('services.pixelforge.connect_timeout_seconds'))
            ->timeout((int) config('services.pixelforge.timeout_seconds'))
            ->acceptJson();
    }
}

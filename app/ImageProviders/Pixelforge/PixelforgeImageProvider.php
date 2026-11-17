<?php

namespace App\ImageProviders\Pixelforge;

use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Estimate;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\ImageProvider;
use App\ImageProviders\Rates;
use App\ValueObjects\Money;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * @phpstan-type Pricing array{
 *     rates: array<string, string>,
 *     tokens: array{
 *         characters_per_text_token: int,
 *         per_reference_image: int,
 *         output: array<string, array<string, int>>,
 *         reasoning: array<string, array{min: int, max: int}>,
 *     },
 * }
 */
final class PixelforgeImageProvider implements ImageProvider
{
    /** Rates are in dollars per 1M tokens. */
    private const int TOKENS_PER_RATE = 1_000_000;

    private const array RATE_TYPES = ['input_text_tokens', 'input_image_tokens', 'output_tokens', 'reasoning_tokens'];

    public function estimate(string $prompt, Size $size, Quality $quality, array $referenceImages): Estimate
    {
        $pricing = $this->pricing();
        $tokens = $pricing['tokens'];

        // One text token per `characters_per_text_token` Unicode characters, rounded up, then −15%
        // rounded down and +15% rounded up (ADR 0024; the pricing rules check the 15%).
        $textTokens = $this->ceilDiv(mb_strlen($prompt), $tokens['characters_per_text_token']);
        $imageTokens = $tokens['per_reference_image'] * count($referenceImages);
        $outputTokens = $tokens['output'][$quality->value][$size->value];
        $reasoningTokens = $tokens['reasoning'][$quality->value];

        $rates = new Rates(
            Money::fromDecimalString($pricing['rates']['input_text_tokens']),
            Money::fromDecimalString($pricing['rates']['input_image_tokens']),
            Money::fromDecimalString($pricing['rates']['output_tokens']),
            Money::fromDecimalString($pricing['rates']['reasoning_tokens']),
        );

        $minimumCost = $this->providerCost($rates, intdiv($textTokens * 85, 100), $imageTokens, $outputTokens, $reasoningTokens['min']);
        $maximumCost = $this->providerCost($rates, $this->ceilDiv($textTokens * 115, 100), $imageTokens, $outputTokens, $reasoningTokens['max']);

        return new Estimate($minimumCost->plusMarkup(), $maximumCost->plusMarkup(), $rates);
    }

    /**
     * The current pricing, cached for the configured time (ADR 0011, ADR 0032). A failed or
     * unusable response throws, so it is never cached.
     *
     * @return Pricing
     */
    private function pricing(): array
    {
        /** @var Pricing */
        return Cache::remember(
            'pixelforge.pricing',
            (int) config('services.pixelforge.pricing_cache_seconds'),
            fn (): array => $this->fetchPricing(),
        );
    }

    /**
     * @return Pricing
     */
    private function fetchPricing(): array
    {
        try {
            $pricing = $this->http()->get('pricing')->throw()->json() ?? [];
        } catch (ConnectionException|RequestException $exception) {
            throw new PricingUnavailable('Pixelforge pricing could not be fetched.', previous: $exception);
        }

        if (! is_array($pricing) || Validator::make($pricing, $this->pricingRules())->fails()) {
            throw new PricingUnavailable('Pixelforge pricing has an unexpected shape.');
        }

        /** @var Pricing */
        return $pricing;
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

<?php

namespace App\ImageProviders\Pixelforge;

use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Rates;
use App\ValueObjects\Money;
use LogicException;

/**
 * Pixelforge's pricing: its rates and how it counts tokens
 * (docs/spec/pixelforge-api.md#pricing-and-token-usage).
 */
final readonly class Pricing
{
    /**
     * @param  list<OutputTokens>  $outputTokens  One per quality and size.
     * @param  list<ReasoningTokens>  $reasoningTokens  One per quality.
     */
    public function __construct(
        public Rates $rates,
        public int $charactersPerTextToken,
        public int $tokensPerReferenceImage,
        private array $outputTokens,
        private array $reasoningTokens,
    ) {}

    /**
     * Maps a validated `GET /v1/pricing` response, read once where it arrives (ADR 0041).
     *
     * @param  array<string, mixed>  $response
     */
    public static function fromResponse(array $response): self
    {
        $outputTokens = [];
        $reasoningTokens = [];

        foreach (Quality::cases() as $quality) {
            foreach (Size::cases() as $size) {
                $outputTokens[] = new OutputTokens($quality, $size, (int) data_get($response, "tokens.output.{$quality->value}.{$size->value}"));
            }

            $reasoningTokens[] = new ReasoningTokens(
                $quality,
                (int) data_get($response, "tokens.reasoning.{$quality->value}.min"),
                (int) data_get($response, "tokens.reasoning.{$quality->value}.max"),
            );
        }

        return new self(
            new Rates(
                Money::fromDecimalString((string) data_get($response, 'rates.input_text_tokens')),
                Money::fromDecimalString((string) data_get($response, 'rates.input_image_tokens')),
                Money::fromDecimalString((string) data_get($response, 'rates.output_tokens')),
                Money::fromDecimalString((string) data_get($response, 'rates.reasoning_tokens')),
            ),
            (int) data_get($response, 'tokens.characters_per_text_token'),
            (int) data_get($response, 'tokens.per_reference_image'),
            $outputTokens,
            $reasoningTokens,
        );
    }

    public function outputTokens(Quality $quality, Size $size): int
    {
        foreach ($this->outputTokens as $outputTokens) {
            if ($outputTokens->quality === $quality && $outputTokens->size === $size) {
                return $outputTokens->tokens;
            }
        }

        throw new LogicException("No output tokens for {$quality->value} {$size->value}.");
    }

    public function reasoningTokens(Quality $quality): ReasoningTokens
    {
        foreach ($this->reasoningTokens as $reasoningTokens) {
            if ($reasoningTokens->quality === $quality) {
                return $reasoningTokens;
            }
        }

        throw new LogicException("No reasoning tokens for {$quality->value}.");
    }
}

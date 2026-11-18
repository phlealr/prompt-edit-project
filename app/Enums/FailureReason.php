<?php

namespace App\Enums;

/**
 * Why a generation failed, in our own terms (ADR 0025).
 */
enum FailureReason: string
{
    case PromptRejected = 'prompt_rejected';
    case ProviderFailed = 'provider_failed';
    case ProviderUnavailable = 'provider_unavailable';
    case PriceChanged = 'price_changed';

    /**
     * The message the user sees (docs/features/generating.md, "After submitting").
     */
    public function message(): string
    {
        return match ($this) {
            self::PromptRejected => "Your prompt was rejected by the image provider's content policy. You were not charged.",
            self::ProviderFailed => 'The image could not be generated. You were not charged. Please try again.',
            self::ProviderUnavailable => 'The image service was busy or unavailable, so we gave up. You were not charged. Please try again.',
            self::PriceChanged => "Prices changed after your estimate, so this generation wasn't submitted. You were not charged. Check the new estimate and submit again.",
        };
    }
}

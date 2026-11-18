<?php

namespace App\Models;

use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Rates;
use App\ValueObjects\Money;
use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One request by a user for one image, from submission to its final status (ADR 0025).
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string|null $provider_id
 * @property string|null $provider_response
 * @property bool $may_exist_at_provider
 * @property Money|null $provider_cost
 * @property string|null $image_url
 * @property Carbon|null $image_url_expires_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property string $prompt
 * @property Size $size
 * @property Quality $quality
 * @property list<string> $reference_images
 * @property GenerationStatus $status
 * @property FailureReason|null $failure_reason
 * @property Rates $rates
 * @property Money $estimate_minimum
 * @property Money $estimate_maximum
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['uuid', 'user_id', 'prompt', 'size', 'quality', 'reference_images', 'status', 'rates', 'estimate_minimum', 'estimate_maximum', 'provider_id', 'provider_response', 'failure_reason', 'may_exist_at_provider', 'provider_cost', 'image_url', 'image_url_expires_at', 'started_at', 'completed_at'])]
class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => Size::class,
            'quality' => Quality::class,
            'reference_images' => 'array',
            'may_exist_at_provider' => 'boolean',
            'image_url_expires_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => GenerationStatus::class,
            'failure_reason' => FailureReason::class,
        ];
    }

    /**
     * The rates the hold was computed with, kept in four nanodollar columns.
     *
     * @return Attribute<Rates, Rates>
     */
    protected function rates(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): Rates => new Rates(
                Money::fromNanodollars((int) $attributes['rate_input_text_tokens']),
                Money::fromNanodollars((int) $attributes['rate_input_image_tokens']),
                Money::fromNanodollars((int) $attributes['rate_output_tokens']),
                Money::fromNanodollars((int) $attributes['rate_reasoning_tokens']),
            ),
            set: fn (Rates $rates): array => [
                'rate_input_text_tokens' => $rates->inputTextTokens->nanodollars(),
                'rate_input_image_tokens' => $rates->inputImageTokens->nanodollars(),
                'rate_output_tokens' => $rates->outputTokens->nanodollars(),
                'rate_reasoning_tokens' => $rates->reasoningTokens->nanodollars(),
            ],
        );
    }

    /**
     * @return Attribute<Money, Money>
     */
    protected function estimateMinimum(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): Money => Money::fromNanodollars($value),
            set: fn (Money $amount): int => $amount->nanodollars(),
        );
    }

    /**
     * @return Attribute<Money, Money>
     */
    protected function estimateMaximum(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): Money => Money::fromNanodollars($value),
            set: fn (Money $amount): int => $amount->nanodollars(),
        );
    }

    /**
     * What the provider billed, in nanodollars, as Money; null until succeeded.
     *
     * @return Attribute<Money|null, Money|null>
     */
    protected function providerCost(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): ?Money => $value === null ? null : Money::fromNanodollars($value),
            set: fn (?Money $amount): ?int => $amount?->nanodollars(),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

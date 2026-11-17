<?php

namespace App\Models;

use App\Enums\CreditTransactionType;
use App\ValueObjects\Money;
use Database\Factories\CreditTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A ledger row: one credit movement, only ever inserted (ADR 0006, ADR 0036).
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $generation_id
 * @property CreditTransactionType $type
 * @property Money $amount
 * @property Carbon $created_at
 */
#[Fillable(['user_id', 'generation_id', 'type', 'amount'])]
class CreditTransaction extends Model
{
    /** @use HasFactory<CreditTransactionFactory> */
    use HasFactory;

    public const null UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Ledger rows are never updated.'));
        static::deleting(fn () => throw new LogicException('Ledger rows are never deleted.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CreditTransactionType::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * The amount in nanodollars, as Money.
     *
     * @return Attribute<Money, Money>
     */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): Money => Money::fromNanodollars($value),
            set: fn (Money $amount): int => $amount->nanodollars(),
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

<?php

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * An exact amount of money, as an integer number of nanodollars (ADR 0005).
 */
final readonly class Money
{
    private const int NANODOLLARS_PER_DOLLAR = 1_000_000_000;

    /** One ten-thousandth of a dollar, the smallest step users see (ADR 0007). */
    private const int NANODOLLARS_PER_TEN_THOUSANDTH = 100_000;

    private function __construct(private int $nanodollars) {}

    public static function fromNanodollars(int $nanodollars): self
    {
        return new self($nanodollars);
    }

    /**
     * Reads a decimal dollar string such as "0.373875000" by its digits, never through a float.
     */
    public static function fromDecimalString(string $decimal): self
    {
        if (preg_match('/^(?<sign>-?)(?<dollars>\d+)(?:\.(?<fraction>\d{1,9}))?\z/', $decimal, $parts) !== 1) {
            throw new InvalidArgumentException("[{$decimal}] is not an exact dollar amount.");
        }

        $dollars = (int) $parts['dollars'];
        $fraction = (int) str_pad($parts['fraction'] ?? '', 9, '0');
        $nanodollars = $dollars * self::NANODOLLARS_PER_DOLLAR + $fraction;

        return new self($parts['sign'] === '-' ? -$nanodollars : $nanodollars);
    }

    public function nanodollars(): int
    {
        return $this->nanodollars;
    }

    /**
     * The charge for this provider cost: cost × 5 / 4, rounded up to a whole nanodollar (ADR 0005).
     */
    public function plusMarkup(): self
    {
        if ($this->nanodollars < 0) {
            throw new InvalidArgumentException('A provider cost cannot be negative.');
        }

        $timesFive = $this->nanodollars * 5;
        $charge = intdiv($timesFive, 4);

        if ($timesFive % 4 !== 0) {
            $charge++;
        }

        return new self($charge);
    }

    /**
     * The 4-decimal format users see, rounded half up away from zero (ADR 0007, ADR 0031).
     */
    public function forUser(): string
    {
        $halfStep = intdiv(self::NANODOLLARS_PER_TEN_THOUSANDTH, 2);
        $tenThousandths = intdiv(abs($this->nanodollars) + $halfStep, self::NANODOLLARS_PER_TEN_THOUSANDTH);

        return $this->format($tenThousandths, 4);
    }

    /**
     * The exact 9-decimal format operators see, and users see on hover (ADR 0007).
     */
    public function exact(): string
    {
        return $this->format(abs($this->nanodollars), 9);
    }

    /**
     * Writes a positive count of 10^-$decimals dollar steps as "$D.DDDD", with this amount's sign.
     */
    private function format(int $steps, int $decimals): string
    {
        $stepsPerDollar = 10 ** $decimals;
        $sign = $this->nanodollars < 0 ? '-' : '';
        $fraction = str_pad((string) ($steps % $stepsPerDollar), $decimals, '0', STR_PAD_LEFT);

        return $sign.'$'.intdiv($steps, $stepsPerDollar).'.'.$fraction;
    }
}

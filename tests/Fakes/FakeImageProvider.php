<?php

namespace Tests\Fakes;

use App\Enums\Quality;
use App\Enums\Size;
use App\ImageProviders\Estimate;
use App\ImageProviders\Exceptions\PricingUnavailable;
use App\ImageProviders\ImageProvider;
use App\ImageProviders\Rates;
use App\ValueObjects\Money;

/**
 * An ImageProvider for tests above the provider boundary: it answers from its properties and never
 * reaches the network. Bind it with fakeImageProvider() from Pest.php.
 */
final class FakeImageProvider implements ImageProvider
{
    public Estimate $estimate;

    public bool $pricingUnavailable = false;

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
    }

    public function estimate(string $prompt, Size $size, Quality $quality, array $referenceImages): Estimate
    {
        if ($this->pricingUnavailable) {
            throw new PricingUnavailable('Fake pricing is unavailable.');
        }

        return $this->estimate;
    }
}

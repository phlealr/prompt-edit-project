<?php

use App\ValueObjects\Money;

it('reads a provider decimal string as exact nanodollars', function (string $decimal, int $nanodollars) {
    expect(Money::fromDecimalString($decimal)->nanodollars())->toBe($nanodollars);
})->with([
    'nine decimals' => ['0.373875000', 373_875_000],
    'two decimals' => ['5.00', 5_000_000_000],
    'no decimals' => ['12', 12_000_000_000],
    'one nanodollar' => ['0.000000001', 1],
    'negative' => ['-0.5', -500_000_000],
]);

it('rejects a decimal string that is not an exact amount', function (string $decimal) {
    Money::fromDecimalString($decimal);
})->with(['', 'abc', '1.0000000001', '1e3', '0.1.2', ' 1.00', "1.00\n"])->throws(InvalidArgumentException::class);

it('adds the markup to a provider cost, rounded up to a whole nanodollar', function (int $cost, int $percent, int $charge) {
    expect(Money::fromNanodollars($cost)->plusMarkup($percent)->nanodollars())->toBe($charge);
})->with([
    'worked example at 25%' => [373_875_000, 25, 467_343_750],
    'one nanodollar rounds 1.25 up' => [1, 25, 2],
    'divisible by four' => [4, 25, 5],
    'zero' => [0, 25, 0],
    '30% of 100' => [100, 30, 130],
    '30% of 1 rounds 1.3 up' => [1, 30, 2],
    'no markup' => [373_875_000, 0, 373_875_000],
]);

it('refuses a negative markup', function () {
    Money::fromNanodollars(100)->plusMarkup(-1);
})->throws(InvalidArgumentException::class);

it('refuses to add the markup to a negative amount', function () {
    Money::fromNanodollars(-1)->plusMarkup(25);
})->throws(InvalidArgumentException::class);

it('formats an amount for users with 4 decimals, rounded half up', function (int $nanodollars, string $formatted) {
    expect(Money::fromNanodollars($nanodollars)->forUser())->toBe($formatted);
})->with([
    'worked example' => [467_343_750, '$0.4673'],
    'ten dollars' => [10_000_000_000, '$10.0000'],
    'half rounds up' => [50_000, '$0.0001'],
    'below half rounds down' => [49_999, '$0.0000'],
    'rounds up into dollars' => [999_950_000, '$1.0000'],
    'negative' => [-467_343_750, '-$0.4673'],
    'negative half rounds away from zero' => [-50_000, '-$0.0001'],
]);

it('formats an amount for operators with all 9 decimals', function (int $nanodollars, string $formatted) {
    expect(Money::fromNanodollars($nanodollars)->exact())->toBe($formatted);
})->with([
    'worked example' => [467_343_750, '$0.467343750'],
    'one nanodollar' => [1, '$0.000000001'],
    'dollars' => [12_345_000_000_001, '$12345.000000001'],
    'negative' => [-467_343_750, '-$0.467343750'],
]);

it('serializes to the user format and the exact amount for the frontend', function () {
    expect(json_encode(Money::fromNanodollars(467_343_750)))
        ->toBe('{"user":"$0.4673","exact":"$0.467343750"}');
});

it('negates an amount, as a hold of the estimate\'s maximum', function () {
    expect(Money::fromNanodollars(467_343_750)->negate()->nanodollars())->toBe(-467_343_750);
});

it('makes an amount of whole dollars', function () {
    expect(Money::fromWholeDollars(10)->nanodollars())->toBe(10_000_000_000);
});

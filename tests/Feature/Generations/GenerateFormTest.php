<?php

use App\Models\CreditTransaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects a guest opening the generate page to sign in', function () {
    $this->get(route('generate'))->assertRedirect(route('login'));
});

it('shows every size and quality, with 1024x1024 and medium selected', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('generate'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('generations/Create')
            ->where('sizes', ['1024x1024', '1536x1024', '1024x1536'])
            ->where('qualities', ['low', 'medium', 'high'])
            ->where('defaults', ['size' => '1024x1024', 'quality' => 'medium'])
        );
});

it('shows the user\'s balance with 4 decimals and the exact amount', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(5_000_000_000)->create();
    CreditTransaction::factory()->for($user)->purchase(123_456_789)->create();

    $this->actingAs($user)
        ->get(route('generate'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('balance', ['user' => '$5.1235', 'exact' => '$5.123456789'])
        );
});

it('says whether the balance covers the estimate\'s maximum', function (int $balance, bool $enoughCredits) {
    fakeImageProvider(); // estimate maximum: 20,000,000 nanodollars
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase($balance)->create();

    $this->actingAs($user)
        ->postJson(route('generate.estimate'), ['prompt' => 'A cat', 'size' => '1024x1024', 'quality' => 'medium'])
        ->assertOk()
        ->assertJsonPath('enoughCredits', $enoughCredits);
})->with([
    'one nanodollar short' => [19_999_999, false],
    'exactly the maximum' => [20_000_000, true],
    'more than the maximum' => [20_000_001, true],
]);

it('returns the estimate range, formatted for users with the exact amounts', function () {
    fakeImageProvider();

    $this->actingAs(User::factory()->create())
        ->postJson(route('generate.estimate'), [
            'prompt' => 'A lighthouse at dawn',
            'size' => '1536x1024',
            'quality' => 'high',
            'reference_images' => ['https://example.com/a.png', 'https://example.com/a.png'],
        ])
        ->assertOk()
        ->assertExactJson([
            'minimum' => ['user' => '$0.0100', 'exact' => '$0.010000000'],
            'maximum' => ['user' => '$0.0200', 'exact' => '$0.020000000'],
            'balance' => ['user' => '$0.0000', 'exact' => '$0.000000000'],
            'enoughCredits' => false,
        ]);
});

it('passes the prompt, options and every reference image URL to the provider', function () {
    $provider = fakeImageProvider();

    $this->actingAs(User::factory()->create())
        ->postJson(route('generate.estimate'), [
            'prompt' => 'A cat',
            'size' => '1024x1536',
            'quality' => 'low',
            'reference_images' => ['https://example.com/a.png', 'https://example.com/a.png'],
        ])
        ->assertOk();

    expect($provider->estimatedFor)->toBe([
        'prompt' => 'A cat',
        'size' => '1024x1536',
        'quality' => 'low',
        'reference_images' => ['https://example.com/a.png', 'https://example.com/a.png'],
    ]);
});

it('estimates an empty prompt while the user has not typed yet', function () {
    fakeImageProvider();

    $this->actingAs(User::factory()->create())
        ->postJson(route('generate.estimate'), ['prompt' => '', 'size' => '1024x1024', 'quality' => 'medium'])
        ->assertOk();
});

it('answers 503 with "Price unavailable right now" when pricing cannot be fetched', function () {
    fakeImageProvider()->pricingUnavailable = true;

    $this->actingAs(User::factory()->create())
        ->postJson(route('generate.estimate'), ['prompt' => 'A cat', 'size' => '1024x1024', 'quality' => 'medium'])
        ->assertStatus(503)
        ->assertExactJson(['message' => 'Price unavailable right now']);
});

it('rejects an estimate for an unknown size or quality, or more than 4 reference images', function (array $input, string $field) {
    fakeImageProvider();

    $this->actingAs(User::factory()->create())
        ->postJson(route('generate.estimate'), [
            'prompt' => 'A cat', 'size' => '1024x1024', 'quality' => 'medium', ...$input,
        ])
        ->assertJsonValidationErrors($field);
})->with([
    'unknown size' => [['size' => '512x512'], 'size'],
    'unknown quality' => [['quality' => 'ultra'], 'quality'],
    'five reference images' => [['reference_images' => array_fill(0, 5, 'https://example.com/a.png')], 'reference_images'],
]);

it('refuses an estimate to a guest', function () {
    $this->postJson(route('generate.estimate'), ['prompt' => 'A cat', 'size' => '1024x1024', 'quality' => 'medium'])
        ->assertUnauthorized();
});

<?php

use App\Actions\Generations\ApplyProviderUpdate;
use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\ImageProviders\ProviderUpdate;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

it('defaults the markup to 25%', function () {
    $credits = require config_path('credits.php');

    expect($credits['markup_percent'])->toBe(25);
});

it('estimates and holds with the configured markup, and keeps it on the generation', function () {
    config()->set('credits.markup_percent', 40);
    fakeImageProvider(); // provider costs: $0.008 to $0.016
    Queue::fake();
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(1_000_000_000)->create();

    // 16,000,000 × 140 / 100 = 22,400,000.
    $this->actingAs($user)
        ->postJson(route('generate.estimate'), ['prompt' => 'A cat', 'size' => '1024x1024', 'quality' => 'low'])
        ->assertJsonPath('maximum.exact', '$0.022400000');

    $this->actingAs($user)->post(route('generations.store'), ['prompt' => 'A cat', 'size' => '1024x1024', 'quality' => 'low']);

    $generation = Generation::query()->sole();
    expect($generation->markup_percent)->toBe(40)
        ->and($generation->estimate_maximum->nanodollars())->toBe(22_400_000)
        ->and(CreditTransaction::query()->where('type', CreditTransactionType::Hold)->sole()->amount->nanodollars())->toBe(-22_400_000);
});

it('charges the markup the generation was held with, even after the setting changed', function () {
    config()->set('credits.markup_percent', 25);
    $generation = Generation::factory()->withHold()->create([
        'status' => GenerationStatus::Processing,
        'provider_id' => 'gen_1',
        'markup_percent' => 40,
    ]);

    app(ApplyProviderUpdate::class)->handle($generation, new ProviderUpdate(
        'gen_1', GenerationStatus::Succeeded, '{}', providerCost: Money::fromNanodollars(373_875_000),
    ));

    // 373,875,000 × 140 / 100 = 523,425,000.
    expect(CreditTransaction::query()->where('generation_id', $generation->id)->where('type', CreditTransactionType::Charge)->sole()->amount->nanodollars())
        ->toBe(-523_425_000);
});

it('shows the configured markup on the home page', function () {
    config()->set('credits.markup_percent', 30);

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('markupPercent', 30));
});

it('refuses a markup that is not a whole number from 0 to 1000', function (string $value) {
    $_ENV['MARKUP_PERCENT'] = $_SERVER['MARKUP_PERCENT'] = $value;

    try {
        require config_path('credits.php');
    } finally {
        unset($_ENV['MARKUP_PERCENT'], $_SERVER['MARKUP_PERCENT']);
    }
})->with(['empty' => [''], 'not a number' => ['abc'], 'decimal' => ['25.5'], 'negative' => ['-5'], 'too large' => ['1001']])
    ->throws(InvalidArgumentException::class);

it('accepts a whole-number markup from the environment', function () {
    $_ENV['MARKUP_PERCENT'] = $_SERVER['MARKUP_PERCENT'] = '30';

    try {
        expect((require config_path('credits.php'))['markup_percent'])->toBe(30);
    } finally {
        unset($_ENV['MARKUP_PERCENT'], $_SERVER['MARKUP_PERCENT']);
    }
});

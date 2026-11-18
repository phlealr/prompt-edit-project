<?php

use App\Enums\GenerationStatus;
use App\Enums\ImageProviderName;
use App\ImageProviders\ImageProvider;
use App\ImageProviders\ImageProviders;
use App\Jobs\Generations\PollGenerationsJob;
use App\Jobs\Generations\SubmitGenerationJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeImageProvider;

/**
 * Binds one fake as the generation's provider (by name) and another as the default for new work, so
 * a test can see which one was used.
 */
function twoProviders(): array
{
    $generationsProvider = new FakeImageProvider;
    $defaultProvider = new FakeImageProvider;
    app()->instance('image-providers.pixelforge', $generationsProvider);
    app()->instance(ImageProvider::class, $defaultProvider);

    return [$generationsProvider, $defaultProvider];
}

it('records the provider named in configuration on a submitted generation', function () {
    fakeImageProvider();
    Queue::fake();
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchase(1_000_000_000)->create();

    $this->actingAs($user)->post(route('generations.store'), ['prompt' => 'A cat', 'size' => '1024x1024', 'quality' => 'low']);

    expect(Generation::query()->sole()->provider)->toBe(ImageProviderName::Pixelforge);
});

it('names the configured default provider', function () {
    expect(app(ImageProviders::class)->default()->name())->toBe(ImageProviderName::Pixelforge)
        ->and(app(ImageProviders::class)->for(ImageProviderName::Pixelforge)->name())->toBe(ImageProviderName::Pixelforge);
});

it('sends a pending generation through its own provider, not the default', function () {
    [$generationsProvider, $defaultProvider] = twoProviders();
    $generation = Generation::factory()->withHold()->create();

    SubmitGenerationJob::dispatchSync($generation->id);

    expect($generationsProvider->submitted)->toBe([$generation->uuid])
        ->and($defaultProvider->submitted)->toBe([])
        ->and($defaultProvider->currentRatesCalls)->toBe(0);
});

it('polls a generation through its own provider, not the default', function () {
    [$generationsProvider, $defaultProvider] = twoProviders();
    $generation = Generation::factory()->withHold()->create(['status' => GenerationStatus::Processing, 'provider_id' => 'gen_1']);
    $generation->forceFill(['updated_at' => now()->subMinutes(5)])->saveQuietly();

    PollGenerationsJob::dispatchSync();

    expect($generationsProvider->fetched)->toBe(['gen_1'])
        ->and($defaultProvider->fetched)->toBe([]);
});

it('answers 404 to a webhook for a provider we do not have', function () {
    $this->call('POST', '/webhooks/acme', content: '{}')->assertNotFound();
});

it('stores each webhook event with its provider, unique per provider', function () {
    WebhookEvent::query()->create(['provider' => 'pixelforge', 'provider_event_id' => 'evt_1', 'payload' => '{}']);
    WebhookEvent::query()->create(['provider' => 'another', 'provider_event_id' => 'evt_1', 'payload' => '{}']);

    expect(WebhookEvent::query()->where('provider_event_id', 'evt_1')->count())->toBe(2);
});

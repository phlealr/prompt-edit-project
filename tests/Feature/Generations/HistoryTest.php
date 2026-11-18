<?php

use App\Enums\CreditTransactionType;
use App\Enums\FailureReason;
use App\Enums\GenerationStatus;
use App\Enums\Quality;
use App\Enums\Size;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\ValueObjects\Money;
use Inertia\Testing\AssertableInertia as Assert;

it('shows only the user\'s own generations, on every page', function () {
    $user = User::factory()->create();
    Generation::factory()->for($user)->count(21)->create();
    Generation::factory()->for(User::factory())->count(5)->create();

    $this->actingAs($user)->get(route('history'))
        ->assertInertia(fn (Assert $page) => $page->where('generations.total', 21)->has('generations.data', 20));
    $this->actingAs($user)->get(route('history', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('generations.data', 1));
});

it('lists generations newest first, 20 per page, with previous and next links', function () {
    $user = User::factory()->create();
    $oldest = Generation::factory()->for($user)->create(['created_at' => now()->subDays(2)]);
    Generation::factory()->for($user)->count(19)->create(['created_at' => now()->subDay()]);
    $newest = Generation::factory()->for($user)->create(['created_at' => now()]);

    $this->actingAs($user)->get(route('history'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('generations.data.0.id', $newest->id)
            ->where('generations.prev_page_url', null)
            ->where('generations.next_page_url', route('history', ['page' => 2]))
        );
    $this->actingAs($user)->get(route('history', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('generations.data.0.id', $oldest->id)
            ->where('generations.prev_page_url', route('history', ['page' => 1]))
            ->where('generations.next_page_url', null)
        );
});

it('shows each card\'s status, hint, prompt, options, estimate and creation time', function () {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->create([
        'prompt' => 'A lighthouse at dawn',
        'size' => Size::Landscape,
        'quality' => Quality::High,
        'reference_images' => ['https://example.com/a.png', 'https://example.com/a.png'],
        'status' => GenerationStatus::Queued,
        'estimate_minimum' => Money::fromNanodollars(357_781_250),
        'estimate_maximum' => Money::fromNanodollars(467_343_750),
        'created_at' => '2026-11-18 09:30:00',
    ]);

    $this->actingAs($user)->get(route('history'))
        ->assertInertia(fn (Assert $page) => $page->where('generations.data.0', [
            'id' => $generation->id,
            'prompt' => 'A lighthouse at dawn',
            'size' => '1536x1024',
            'quality' => 'high',
            'referenceImageCount' => 2,
            'status' => 'queued',
            'hint' => "In the provider's queue",
            'estimateMinimum' => ['user' => '$0.3578', 'exact' => '$0.357781250'],
            'estimateMaximum' => ['user' => '$0.4673', 'exact' => '$0.467343750'],
            'charge' => null,
            'createdAt' => '2026-11-18T09:30:00Z',
        ]));
});

it('gives each status its hint', function (GenerationStatus $status, ?FailureReason $reason, ?string $hint) {
    $user = User::factory()->create();
    Generation::factory()->for($user)->create(['status' => $status, 'failure_reason' => $reason]);

    $this->actingAs($user)->get(route('history'))
        ->assertInertia(fn (Assert $page) => $page->where('generations.data.0.hint', $hint));
})->with([
    'pending' => [GenerationStatus::Pending, null, 'Waiting for the image service'],
    'queued' => [GenerationStatus::Queued, null, "In the provider's queue"],
    'processing' => [GenerationStatus::Processing, null, 'Generating…'],
    'succeeded' => [GenerationStatus::Succeeded, null, null],
    '[fail]' => [GenerationStatus::Failed, FailureReason::ProviderFailed, 'The image could not be generated. You were not charged. Please try again.'],
    '[blocked]' => [GenerationStatus::Failed, FailureReason::PromptRejected, "Your prompt was rejected by the image provider's content policy. You were not charged."],
    '[overloaded]' => [GenerationStatus::Failed, FailureReason::ProviderUnavailable, 'The image service was busy or unavailable, so we gave up. You were not charged. Please try again.'],
]);

it('shows the charge of a succeeded generation, from the ledger', function () {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->create(['status' => GenerationStatus::Succeeded]);
    CreditTransaction::factory()->for($user)->create([
        'type' => CreditTransactionType::Charge,
        'generation_id' => $generation->id,
        'amount' => Money::fromNanodollars(-467_343_750),
    ]);

    $this->actingAs($user)->get(route('history'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('generations.data.0.charge', ['user' => '$0.4673', 'exact' => '$0.467343750']));
});

it('never shows a provider cost, a token count or a provider error code', function () {
    $user = User::factory()->create();
    Generation::factory()->for($user)->create([
        'status' => GenerationStatus::Succeeded,
        'provider_cost' => Money::fromNanodollars(373_875_000),
        'provider_response' => '{"usage":{"output_tokens":6450}}',
    ]);
    Generation::factory()->for($user)->create([
        'status' => GenerationStatus::Failed,
        'failure_reason' => FailureReason::ProviderFailed,
        'provider_response' => '{"error":{"code":"internal_error"}}',
    ]);

    $props = (string) json_encode($this->actingAs($user)->get(route('history'))->inertiaProps('generations'));

    expect($props)->not->toContain('0.3738')
        ->not->toContain('373875000')
        ->not->toContain('6450')
        ->not->toContain('internal_error');
});

it('shows an empty history to a user with no generations', function () {
    $this->actingAs(User::factory()->create())->get(route('history'))
        ->assertInertia(fn (Assert $page) => $page->has('generations.data', 0));
});

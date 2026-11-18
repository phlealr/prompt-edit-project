<?php

use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Jobs\Generations\SubmitGenerationJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A user with this many nanodollars of purchases. The fake provider's estimate maximum is
 * 20,000,000 nanodollars ($0.02).
 */
function userWithBalance(int $nanodollars): User
{
    $user = User::factory()->create();

    if ($nanodollars !== 0) {
        CreditTransaction::factory()->for($user)->purchase($nanodollars)->create();
    }

    return $user;
}

/**
 * @return array<string, mixed>
 */
function validGeneration(array $overrides = []): array
{
    return [
        'prompt' => 'A lighthouse on a cliff at dawn',
        'size' => '1536x1024',
        'quality' => 'high',
        'reference_images' => ['https://example.com/style.png'],
        ...$overrides,
    ];
}

function expectNothingCreated(): void
{
    expect(Generation::query()->count())->toBe(0)
        ->and(CreditTransaction::query()->where('type', CreditTransactionType::Hold)->count())->toBe(0);
    Queue::assertNothingPushed();
}

beforeEach(function () {
    $this->provider = fakeImageProvider();
    Queue::fake();
});

it('creates a pending generation with its rates, a hold of minus the maximum, and a queued job', function () {
    $user = userWithBalance(1_000_000_000);

    $this->actingAs($user)->post(route('generations.store'), validGeneration())
        ->assertRedirect(route('history'));

    $generation = Generation::query()->sole();
    expect($generation->user_id)->toBe($user->id)
        ->and($generation->status)->toBe(GenerationStatus::Pending)
        ->and($generation->prompt)->toBe('A lighthouse on a cliff at dawn')
        ->and($generation->size->value)->toBe('1536x1024')
        ->and($generation->quality->value)->toBe('high')
        ->and($generation->reference_images)->toBe(['https://example.com/style.png'])
        ->and($generation->rates->outputTokens->nanodollars())->toBe(40_000_000_000)
        ->and($generation->rates->reasoningTokens->nanodollars())->toBe(12_500_000_000)
        ->and($generation->estimate_minimum->nanodollars())->toBe(10_000_000)
        ->and($generation->estimate_maximum->nanodollars())->toBe(20_000_000);

    $hold = CreditTransaction::query()->where('type', CreditTransactionType::Hold)->sole();
    expect($hold->generation_id)->toBe($generation->id)
        ->and($hold->user_id)->toBe($user->id)
        ->and($hold->amount->nanodollars())->toBe(-20_000_000);

    Queue::assertPushed(SubmitGenerationJob::class, fn (SubmitGenerationJob $job) => $job->generationId === $generation->id);
    Queue::assertPushed(SubmitGenerationJob::class, 1);
});

it('estimates the submission from the same input it stores', function () {
    $this->actingAs(userWithBalance(1_000_000_000))->post(route('generations.store'), validGeneration([
        'reference_images' => ['https://example.com/a.png', 'https://example.com/a.png'],
    ]));

    expect($this->provider->estimatedPrompt)->toBe('A lighthouse on a cliff at dawn')
        ->and($this->provider->estimatedSize?->value)->toBe('1536x1024')
        ->and($this->provider->estimatedQuality?->value)->toBe('high')
        ->and($this->provider->estimatedReferenceImages)->toBe(['https://example.com/a.png', 'https://example.com/a.png']);
});

it('creates the generation, its hold and its job in one transaction', function () {
    config()->set('queue.default', 'database');
    Queue::swap(app('queue'));
    CreditTransaction::creating(function (CreditTransaction $row) {
        if ($row->type === CreditTransactionType::Hold) {
            throw new RuntimeException('The hold could not be written.');
        }
    });
    $user = userWithBalance(1_000_000_000);

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs($user)->post(route('generations.store'), validGeneration()))
        ->toThrow(RuntimeException::class);

    expect(Generation::query()->count())->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(0);
});

it('lands on the history page with "Generation submitted" and the new generation first', function () {
    $user = userWithBalance(1_000_000_000);
    $older = Generation::factory()->for($user)->create(['created_at' => now()->subHour()]);
    Generation::factory()->for(User::factory())->create();

    $this->actingAs($user)->post(route('generations.store'), validGeneration());

    $newest = Generation::query()->whereBelongsTo($user)->latest('id')->firstOrFail();
    $this->actingAs($user)->get(route('history'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('generations/Index')
            ->hasFlash('status', 'Generation submitted')
            ->has('generations.data', 2)
            ->where('generations.data.0.id', $newest->id)
            ->where('generations.data.0.status', 'pending')
            ->where('generations.data.1.id', $older->id)
        );
});

it('accepts a prompt of exactly 4,000 Unicode characters', function () {
    $this->actingAs(userWithBalance(1_000_000_000))
        ->post(route('generations.store'), validGeneration(['prompt' => str_repeat('é', 4000)]))
        ->assertSessionHasNoErrors();

    expect(Generation::query()->count())->toBe(1);
});

it('rejects invalid input with a message on that field and creates nothing', function (array $input, string $field) {
    $this->actingAs(userWithBalance(1_000_000_000))
        ->post(route('generations.store'), validGeneration($input))
        ->assertSessionHasErrors($field);

    expectNothingCreated();
})->with([
    'missing prompt' => [['prompt' => ''], 'prompt'],
    'prompt over 4,000 characters' => [['prompt' => str_repeat('a', 4001)], 'prompt'],
    'unknown size' => [['size' => '512x512'], 'size'],
    'unknown quality' => [['quality' => 'ultra'], 'quality'],
    'five reference images' => [['reference_images' => array_fill(0, 5, 'https://example.com/a.png')], 'reference_images'],
    'reference image that is not a URL' => [['reference_images' => ['not a url']], 'reference_images.0'],
    'reference image over ftp' => [['reference_images' => ['ftp://example.com/a.png']], 'reference_images.0'],
    'blank reference image' => [['reference_images' => ['']], 'reference_images.0'],
]);

it('names a reference image plainly in its error', function () {
    $this->actingAs(userWithBalance(1_000_000_000))
        ->post(route('generations.store'), validGeneration(['reference_images' => ['not a url']]))
        ->assertSessionHasErrors(['reference_images.0' => 'The reference image field must be a valid URL.']);
});

it('refuses a submission whose maximum exceeds the balance, and creates nothing', function (int $balance) {
    $this->actingAs(userWithBalance($balance))
        ->post(route('generations.store'), validGeneration())
        ->assertSessionHasErrors(['generation' => 'Not enough credits: this can cost up to $0.0200 and your balance is '.($balance === 0 ? '$0.0000' : '$0.0200')]);

    expectNothingCreated();
})->with([
    'no ledger rows' => [0],
    'one nanodollar short' => [19_999_999],
]);

it('accepts a submission whose maximum equals the balance', function () {
    $this->actingAs(userWithBalance(20_000_000))->post(route('generations.store'), validGeneration())
        ->assertSessionHasNoErrors();

    expect(Generation::query()->count())->toBe(1);
});

it('creates nothing and says why when pricing fails at submission', function () {
    $this->provider->pricingUnavailable = true;

    $this->actingAs(userWithBalance(1_000_000_000))
        ->post(route('generations.store'), validGeneration())
        ->assertSessionHasErrors(['price' => "We couldn't confirm the price, so nothing was submitted."]);

    expectNothingCreated();
});

it('redirects a guest to sign in and creates nothing', function () {
    $this->post(route('generations.store'), validGeneration())->assertRedirect(route('login'));

    expectNothingCreated();
});

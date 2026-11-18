<?php

use App\Jobs\Generations\ApplyWebhookEventJob;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config()->set('services.pixelforge.webhook_secret', 'whsec_test');
    Queue::fake();
});

/**
 * Posts a raw body to the webhook route, as Pixelforge does: no session, no CSRF token.
 */
function postWebhook(string $body, ?string $signature): TestResponse
{
    $server = ['CONTENT_TYPE' => 'application/json'];

    if ($signature !== null) {
        $server['HTTP_PIXELFORGE_SIGNATURE'] = $signature;
    }

    return test()->call('POST', '/webhooks/pixelforge', server: $server, content: $body);
}

it('records a valid event once by its id, dispatches a job to apply it, and answers 200', function () {
    $body = pixelforgeEvent('evt_1', 'generation.processing', ['status' => 'processing']);

    postWebhook($body, pixelforgeSignature($body, now()->timestamp))->assertOk();

    $event = WebhookEvent::query()->sole();
    expect($event->provider_event_id)->toBe('evt_1')
        ->and($event->payload)->toBe($body);
    Queue::assertPushed(ApplyWebhookEventJob::class, fn (ApplyWebhookEventJob $job) => $job->webhookEventId === $event->id);
    Queue::assertPushed(ApplyWebhookEventJob::class, 1);
});

it('answers 200 to the same event delivered twice, and records and dispatches it once', function () {
    $body = pixelforgeEvent('evt_1', 'generation.succeeded', ['status' => 'succeeded']);

    postWebhook($body, pixelforgeSignature($body, now()->timestamp))->assertOk();
    postWebhook($body, pixelforgeSignature($body, now()->timestamp + 10))->assertOk();

    expect(WebhookEvent::query()->count())->toBe(1);
    Queue::assertPushed(ApplyWebhookEventJob::class, 1);
});

it('records a ping and answers 200, and applies nothing', function () {
    $body = (string) json_encode(['id' => 'evt_ping', 'object' => 'event', 'type' => 'ping', 'created_at' => '2026-11-04T14:03:29Z', 'data' => []]);

    postWebhook($body, pixelforgeSignature($body, now()->timestamp))->assertOk();

    expect(WebhookEvent::query()->where('provider_event_id', 'evt_ping')->exists())->toBeTrue();
    Queue::assertNothingPushed();
});

it('answers 400 to a request it cannot trust, and records nothing', function (Closure $request) {
    $body = pixelforgeEvent('evt_1', 'generation.processing');
    [$sentBody, $signature] = $request($body);

    postWebhook($sentBody, $signature)->assertStatus(400);

    expect(WebhookEvent::query()->count())->toBe(0);
    Queue::assertNothingPushed();
})->with([
    'no signature' => [fn (string $body) => [$body, null]],
    'a signature with another secret' => [fn (string $body) => [$body, pixelforgeSignature($body, now()->timestamp, 'whsec_other')]],
    'a signature over a different body' => [fn (string $body) => [$body, pixelforgeSignature($body.' ', now()->timestamp)]],
    'a timestamp more than 5 minutes old' => [fn (string $body) => [$body, pixelforgeSignature($body, now()->timestamp - 301)]],
    'a timestamp more than 5 minutes ahead' => [fn (string $body) => [$body, pixelforgeSignature($body, now()->timestamp + 301)]],
    'a malformed signature header' => [fn (string $body) => [$body, 'garbage']],
    'a signed body that is not an event' => [fn () => ['not json', pixelforgeSignature('not json', now()->timestamp)]],
]);

it('accepts a timestamp exactly 5 minutes off', function () {
    $body = pixelforgeEvent('evt_1', 'generation.processing');

    postWebhook($body, pixelforgeSignature($body, now()->timestamp - 300))->assertOk();
});

it('is registered outside the web middleware group, so no CSRF token or session is needed', function () {
    $route = Route::getRoutes()->getByName('webhooks.pixelforge');

    expect($route)->not->toBeNull()
        ->and($route?->gatherMiddleware())->not->toContain('web')
        ->and($route?->gatherMiddleware())->not->toContain('auth');
});

it('refuses every webhook, even one signed with an empty key, when no secret is configured', function () {
    config()->set('services.pixelforge.webhook_secret', null);
    $body = pixelforgeEvent('evt_1', 'generation.processing');

    postWebhook($body, pixelforgeSignature($body, now()->timestamp, ''))->assertStatus(400);

    expect(WebhookEvent::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

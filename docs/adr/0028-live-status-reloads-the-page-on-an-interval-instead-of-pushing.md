# 0028. Live status reloads the page on an interval instead of pushing

- Status: accepted
- Date: 2026-11-17

## Context

Users see their generations with live status (`docs/spec/assessment.md#what-to-build`). Updates reach
our server from the provider (webhooks or polling, ADR 0010), but the page runs in the user's browser
and only learns of them when it asks. A generation waits a few seconds in the queue and processes for
5 to 90 seconds (`docs/spec/pixelforge-api.md#lifecycle`, `docs/spec/pixelforge-api.md#testing`).
Pushing changes needs a connection held open: a websocket server (Laravel Reverb or a hosted service)
running beside the app, or Server-Sent Events, which in PHP hold a worker per open page.

## Decision

The history page reloads its generations from our server every 3 seconds, through Inertia's built-in
polling, while any generation on the current page is not in a final status, and stops once all are.
No websockets or broadcasting are used.

## Rules it creates

- The history page reloads only while at least one generation on the current page is not final.
- A reload fetches only the history page's own data, never the provider.
- The reload interval (3 seconds) is a constant in the page.
- No broadcasting, websocket or Server-Sent Events code exists in the app.

## Consequences

Live status costs a few lines and no extra infrastructure. A change shows up to 3 seconds late, and
each open history page with running generations makes one small request every 3 seconds. Pushing can
be added later behind the same page without changing how generations are updated.

## Alternatives considered

- **Websockets (Laravel Reverb)**: instant updates, but an extra server process to run and configure,
  and broadcast events from the update action.
- **Server-Sent Events**: no websocket server, but each open page holds a PHP worker, and that worker
  has to check the database in a loop anyway.

## In plain words

While something on the history page is still running, the page asks our server for news every three
seconds and stops once everything is finished. Images take seconds to minutes, so three seconds of
delay doesn't matter, and we avoid running a websocket server.

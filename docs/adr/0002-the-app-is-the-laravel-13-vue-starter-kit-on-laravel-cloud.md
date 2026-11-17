# 0002. The app is built on the Laravel 13 Vue starter kit and deployed on Laravel Cloud

- Status: accepted
- Date: 2026-11-17

## Context

The brief fixes Laravel, Vue and Inertia, requires users to sign in, and requires a public
deployment, suggesting Laravel Cloud (`docs/spec/assessment.md#what-to-build`). The app needs a queue
worker (webhooks must be answered fast and processed in a job) and a scheduler (polling for lost
webhooks), per `docs/spec/pixelforge-api.md#responding-and-retries` and
`docs/spec/pixelforge-api.md#delivery-is-at-least-once-in-any-order`.

## Decision

The app starts from the official Laravel 13 Vue starter kit (Inertia, Vue 3, TypeScript, Tailwind,
shadcn-vue, Wayfinder, authentication through Fortify), not the WorkOS variant. It is deployed on
Laravel Cloud, with a queue worker and the scheduler running there.

## Rules it creates

- The app runs on Laravel 13 and PHP 8.3 or later.
- Frontend pages are Inertia pages in Vue with TypeScript; frontend code calls routes through
  Wayfinder's generated functions, not hard-coded URLs.
- The production environment runs at least one queue worker and the scheduler.

## Consequences

Authentication, layout and frontend tooling exist from the start, so time goes to the graded parts.
We accept the starter kit's structure and dependencies as they come, and remove what we do not use.

## Alternatives considered

- **Wiring Inertia and Vue by hand**: costs time for nothing graded.
- **WorkOS starter kit variant**: needs an external account and adds an integration to explain.
- **Other hosts (Forge with a VPS, Fly, Railway)**: workable, but queue workers and the scheduler
  take more setup than on Laravel Cloud.

## In plain words

I used Laravel's official Vue starter kit so login and the frontend setup were done on day one, and
deployed on Laravel Cloud because it runs the queue worker and scheduler this app needs.

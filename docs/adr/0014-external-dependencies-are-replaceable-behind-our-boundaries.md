# 0014. External dependencies are replaceable behind our boundaries; our own core stays concrete

- Status: accepted
- Date: 2026-11-17

## Context

The user wants the app as modular as possible: changing the queue or changing Pixelforge must be
easy. The brief describes Pixelforge as behaving "like the real providers we integrate with", so the
product works with several providers (`docs/spec/assessment.md#the-provider-pixelforge`). Pixelforge
has its own URLs, error codes, signature format, statuses and token-based pricing
(`docs/spec/pixelforge-api.md`). An interface with a single implementation inside our own code adds
indirection without benefit.

## Decision

The image **provider** is an interface. Pixelforge is one implementation of it, bound in a service
provider. The interface offers estimating a price, submitting a generation, fetching a generation's
current state, turning an incoming webhook into a provider update, and reporting provider usage.
Only our own types cross it: our statuses, our errors, and provider cost as `Money`. Infrastructure
(queue, database, files, cache) is used only through Laravel's abstractions. Use cases, the `Money`
class and the ledger are concrete classes without interfaces, until a second implementation actually
exists.

## Rules it creates

- Only the Pixelforge implementation knows Pixelforge's URLs, headers, error codes, signature format,
  statuses, ids and token pricing; nothing outside it uses Laravel's HTTP client for Pixelforge.
- Provider errors reach the rest of the app only as our own exceptions (for example: rate limited,
  overloaded, prompt rejected).
- Provider cost crosses the boundary as `Money`; token counts and rates never leave the provider
  implementation except as stored raw data for audit.
- Swapping the provider means one new implementation and one binding change; no use case, model,
  controller or page changes.
- Queue, database, files and cache are reached only through Laravel's queue API, Eloquent and the
  query builder, `Storage` and `Cache`. SQL specific to PostgreSQL needs an ADR.
- Our own classes get an interface only when a second implementation exists.

## Consequences

Use cases and money logic can be tested against a fake provider without HTTP, and Pixelforge's
quirks stay in one folder. The boundary costs one interface and a mapping layer: a few more files
than calling Pixelforge directly.

## Alternatives considered

- **Call Pixelforge directly from the use cases**: fewer files, but its error codes, statuses and
  pricing would spread through the app, and switching provider would touch everything.
- **Interfaces everywhere, including our own core**: abstractions with one implementation each,
  adding indirection to explain without making anything replaceable that needs to be.

## In plain words

Everything outside our control (the image provider, the queue, the database, storage) sits behind a
boundary, so swapping Pixelforge for another provider is one new class. Inside, our own logic is
plain classes, because an interface with one implementation would only add indirection.

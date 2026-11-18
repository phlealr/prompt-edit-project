# 0044. Each generation records its provider, and is handled by it

- Status: accepted
- Date: 2026-11-18
- Amends: ADR 0014

## Context

The provider is an interface with one implementation, Pixelforge, bound in a service provider
(ADR 0014). Nothing recorded which provider a generation was sent to, so once a second provider
exists, a generation's charge, its retries, its polling and its webhooks could not be tied to the
provider that holds it. The user wants the app ready to take new providers.

## Decision

Every generation records its provider as an `ImageProviderName` enum (today only `pixelforge`). New
generations go to the provider named in configuration. Everything that acts on an existing
generation (the submission job and its retries, polling, re-sends and webhooks) asks a small registry
for that generation's own provider. Webhooks arrive on a route per provider, `/webhooks/{provider}`,
and each recorded webhook event stores its provider; event ids are unique per provider.

## Rules it creates

- Every generation stores the provider it was sent to; ledger rows reach it through their generation.
- New generations, and the estimate, use the provider named in `services.image_provider`.
- Code acting on an existing generation gets its provider from the registry by the generation's
  provider, never from the default binding.
- A webhook is verified and applied by the provider named in its route; webhook events are unique per
  (provider, event id).
- Adding a provider means one implementation, one enum case and one registry binding.

## Consequences

Generations sent before a provider change keep being polled, retried and charged by the provider that
has them. The operator report still compares with the configured provider's usage only; comparing
several providers is left until a second one exists.

## Alternatives considered

- **Record only**: smaller, but polling, retries and webhooks would still assume one provider.

## In plain words

Each generation remembers which provider made it, and everything that follows up on it asks that same
provider. Adding a provider later is one new class plus one line in a list.

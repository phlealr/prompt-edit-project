# 09. Record and route by provider

- Status: done
- Blocked by: none
- Spec: docs/features/generating.md

## What to build

Each generation records which provider it was sent to, and everything that follows up on it uses that
provider (ADR 0044). New generations and the estimate use the provider named in configuration.
Webhooks arrive on a route per provider and are stored with it.

## Acceptance criteria

- [x] A submitted generation records its provider, the one named in configuration.
- [x] The submission job, its retries, polling and re-sends use the generation's own provider, not
      the default binding.
- [x] Webhooks arrive on `/webhooks/{provider}`; an unknown provider gets `404`; each event is stored
      with its provider, and the same event id from two providers is two events.
- [x] Existing generations are recorded as `pixelforge`.
- [x] Adding a provider needs only an implementation, an enum case and a registry binding.

## Notes

- Decision: record the provider and route by it (ADR 0044). `ImageProviderName` (today only
  `pixelforge`) is stored on each generation and webhook event; `ImageProviders::for()` resolves
  `image-providers.<name>` from the container; the `ImageProvider` binding is the default from
  `services.image_provider` (env `IMAGE_PROVIDER`), used only for new generations, the estimate and
  the operator report.
- The webhook route is `/webhooks/{provider}`, still outside the web group, with only
  `SubstituteBindings` so the name becomes the enum; an unknown name gets 404. Webhook events are
  unique per (provider, event id).
- A webhook naming a generation recorded with another provider is ignored with a warning, like an
  unknown generation. No test drives it: it needs a second provider to name.
- The migration backfilled every existing generation and webhook event as `pixelforge` (52 local
  generations checked). Its `down()` restores a single-column unique index, which only works while
  no two providers share an event id.
- Follow-up when a second provider exists: the operator report compares with the configured
  provider's usage only.
- Adding a provider: an implementation in `app/ImageProviders/<Name>/`, a case in
  `ImageProviderName`, and `$this->app->bind('image-providers.<name>', …)` in `AppServiceProvider`.

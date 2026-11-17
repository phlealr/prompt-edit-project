# Image providers

The boundary between the app and every external image provider (ADR 0014, ADR 0020). Shared rules:
[`CODING_STANDARDS.md`](../../CODING_STANDARDS.md). For anything Pixelforge does, the source is
[`docs/spec/pixelforge-api.md`](../../docs/spec/pixelforge-api.md).

## Layout

- `ImageProvider.php`: the interface. It estimates a price, submits a generation, fetches a
  generation's current state, turns an incoming webhook into a provider update, and reports the
  provider's usage.
- Next to it: the types that cross the boundary (the provider update, the estimate, …). They use our
  enums and `Money`, never provider names or units.
- `Exceptions/`: our exceptions for provider outcomes (rate limited with its retry-after, overloaded,
  prompt rejected, …). Nothing outside this folder sees a provider error code.
- `Pixelforge/`: the only implementation today, bound to the interface in a service provider.

## Rules for an implementation

- It is the only code that knows its provider's URLs, headers, ids, statuses, error codes, signature
  format and pricing units, and it maps each to our types.
- Its settings come from `config('services.<provider>')`, read from `.env`.
- It returns provider cost as exact `Money`, parsed from decimal strings without floats (ADR 0005).
- It keeps the provider's raw response available for audit, but nothing outside it reads that data.

## Pixelforge facts every change here must respect

From the spec; the section names are links into `docs/spec/pixelforge-api.md`:

- Create requests send `Idempotency-Key` = our generation's UUID and our generation id in `metadata`
  (ADR 0009, Idempotency).
- The actual cost uses the **`rates` returned on the generation**, fixed when it was created, not the
  current pricing; the estimate uses current pricing from `GET /v1/pricing`, cached (ADR 0011,
  The generation object, Pricing and token usage).
- Webhook signatures: HMAC-SHA256 of `<timestamp>.<raw body>` with the key's `whsec_` secret,
  compared in constant time; timestamps more than 5 minutes off are rejected (ADR 0010, Verifying
  signatures).
- Usage is billed on the UTC day a generation **completed** (ADR 0016, Usage).

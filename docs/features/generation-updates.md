# Generation updates

- Status: approved
- Sources:
  - Brief: [What to build](../spec/assessment.md#what-to-build) (Pricing, Webhooks),
    [What we look at](../spec/assessment.md#what-we-look-at)
  - Pixelforge: [Retrieve a generation](../spec/pixelforge-api.md#retrieve-a-generation),
    [The generation object](../spec/pixelforge-api.md#the-generation-object),
    [Pricing and token usage](../spec/pixelforge-api.md#pricing-and-token-usage),
    [Rate limits and quotas](../spec/pixelforge-api.md#rate-limits-and-quotas),
    [Webhooks](../spec/pixelforge-api.md#webhooks), [Images](../spec/pixelforge-api.md#images),
    [Usage](../spec/pixelforge-api.md#usage), [Testing](../spec/pixelforge-api.md#testing)
  - ADRs: 0004, 0005, 0006, 0010, 0012, 0014, 0016, 0020, 0025, 0026, 0027

## Goal

Once the provider has accepted a generation, the app follows it to its final status from the
provider's webhooks, or by polling when webhooks are lost, and records exactly what it cost: the
provider cost, and a charge of that cost plus 25%. Failed generations cost nothing. Duplicate and
out-of-order webhooks never change the result or record anything twice. This meets the brief's
**Webhooks** requirement and the after-completion half of **Pricing**.

How the user sees these updates is the `history` spec; how the generation reached the provider is the
`generating` spec.

## Behaviour

This feature has no screen of its own. What the user ends up seeing, through the history page and
their balance:

| Provider says | Generation status | Ledger | User sees |
| --- | --- | --- | --- |
| Left the queue | `processing` | No change | Processing |
| Succeeded | `succeeded`, with provider cost, image URL and its expiry | `release` of the hold, `charge` of cost plus 25% | The image and the exact charge |
| Failed (`generation_failed` or `internal_error`) | `failed` with `provider_failed` | `release` of the hold | "The image could not be generated. You were not charged. Please try again." |

### What a succeeded generation records

- The provider's token usage, its rates, and its raw response, kept for audit (ADR 0014).
- The **provider cost**: each token count × the matching rate **on the generation** (fixed when the
  provider created it), summed exactly in nanodollars (ADR 0005). It equals what Pixelforge bills.
- The **charge**: ceil(provider cost × 5 / 4), never capped at the hold (ADR 0026).
- The image URL and the time it expires (ADR 0012).
- The provider's `started_at` and `completed_at`. The completion time is the provider's, because
  Pixelforge bills on the UTC day it completed, and the operator report must match it (ADR 0016).

### What a failed generation records

The failure reason `provider_failed`, and the provider's error code and message as raw data. Nothing
is resubmitted automatically.

### The webhook endpoint

A public `POST` route that Pixelforge calls, outside CSRF protection. It answers:

| Request | Answer | Effect |
| --- | --- | --- |
| Missing or invalid signature, or timestamp more than 5 minutes off | `400` | None |
| Valid, event id already recorded | `200` | None |
| Valid `ping` | `200` | The event is recorded; nothing is applied |
| Valid generation event | `200` | The event is recorded and a job is dispatched to apply it |

## Flows

### Normal path, by webhook

1. The provider posts `generation.processing`. The endpoint checks the signature against the raw body
   and the timestamp, inserts the event by its id, dispatches the job, and answers `200` (ADR 0010).
2. The job turns the event into a provider update through the provider interface, finds the
   generation by our id in the event's metadata, and calls the action that applies provider updates.
   The generation moves to `processing`.
3. The provider posts `generation.succeeded`. Same steps; the action, in one transaction, sets
   `succeeded`, records the usage, rates, provider cost, image URL, expiry and timestamps, and inserts
   the `release` and the `charge`.

### Failed generation (`[fail]`)

1. The provider posts `generation.failed` with `generation_failed` or `internal_error`.
2. The action sets `failed` with `provider_failed`, keeps the provider's code and message, and inserts
   the `release` only. The user's balance is back where it was before submitting.

### Duplicate webhook (`[duplicate]`, or a resend from the dashboard)

1. The second delivery has an event id already in `webhook_events`.
2. The endpoint answers `200` and dispatches nothing.
3. If two different events carry the same outcome (or the same event slips through twice), the action
   finds the status already there and the ledger's unique (generation, type) rows refuse a second
   `release` or `charge` (ADR 0006, ADR 0025).

### Out-of-order webhooks

1. A retried `generation.processing` arrives after `generation.succeeded`.
2. The action ignores it: a status never moves back or out of a final status (ADR 0025). Nothing is
   written.

### Webhook before the create response

1. A webhook arrives while the generation is still `pending`, because the provider's answer to the
   submission job has not come back yet.
2. The action applies it: moving forward from `pending` is allowed, and the generation stores the
   provider's id from the event. When the submission job's response arrives, the action finds a later
   status and changes nothing.

### Webhook we cannot apply

1. A valid `ping`, or a generation event whose metadata has no generation id of ours, or an id we do
   not have.
2. The endpoint records it and answers `200`, so the provider stops retrying. Nothing is applied; an
   unknown generation logs a warning.

### Webhook job fails

1. The job throws (for example, the database is briefly unavailable). The queue retries it.
2. If every retry fails, the event stays recorded but unapplied, and polling brings the generation to
   its final status.

### Lost webhooks, recovered by polling

1. Every minute, a scheduled job picks generations in `queued` or `processing` not updated or polled
   for 2 minutes (configuration), at most 10 (configuration), oldest first (ADR 0027).
2. For each, it fetches the generation from the provider, records the time it polled, and applies the
   result through the same action. A generation that is final at the provider becomes final here,
   with the same records as by webhook.
3. A `429` ends the run; the rest wait for the next minute. A `503` or network error skips that one
   generation until the next run.
4. A provider answer of `404`, or a generation still not final an hour after it was submitted, logs an
   error and changes nothing. The app never fails a generation the provider accepted.

### Charge above the hold

1. The provider's rates rose between the rate check and the creation (ADR 0026), so the provider cost
   × 1.25 is larger than the hold.
2. The action charges the full amount anyway, logs a warning with the generation, the hold, the charge
   and the difference, and the user's balance can go below zero.
3. The user cannot submit again until a purchase brings the balance up to a new hold (ADR 0006). The
   uncovered charge is visible from the ledger and listed on the operator report.

## Acceptance criteria

Webhook endpoint

- [ ] A request with no signature, a wrong signature, a signature over a different body, or a
      timestamp more than 5 minutes off gets `400`, and no event, job or change is recorded.
- [ ] The signature is checked on the raw request body, compared in constant time.
- [ ] The route accepts requests without a CSRF token or a signed-in user, and never redirects
      (Pixelforge does not follow redirects).
- [ ] A valid event is recorded once in `webhook_events` by its event id, a job is dispatched, and the
      answer is `200`; the request does no other work.
- [ ] The same event delivered twice (`[duplicate]`) is answered `200` both times and recorded and
      dispatched once.
- [ ] A `ping` is recorded and answered `200`, and applies nothing.
- [ ] An event for an unknown generation, or with no generation id in its metadata, is recorded,
      answered `200`, applies nothing and logs a warning.

Applying updates

- [ ] `generation.processing` moves a `queued` or `pending` generation to `processing`.
- [ ] `generation.succeeded` sets `succeeded` and records usage, rates, provider cost, charge, image
      URL, its expiry, and the provider's `started_at` and `completed_at`.
- [ ] For the worked example's usage at the published rates (115 text, 1,530 image, 6,450 output and
      8,000 reasoning tokens), the provider cost is $0.373875000 and the charge is $0.467343750.
- [ ] The provider cost uses the rates on the generation even when the current pricing differs.
- [ ] A succeeded generation has exactly one `release` equal to its hold and one `charge` equal to
      ceil(provider cost × 5 / 4).
- [ ] `[fail]`: the generation ends `failed` with `provider_failed`, the provider's code and message
      are kept, it has one `release` equal to its hold and no `charge`, and the user's balance equals
      the balance before submitting.
- [ ] Both `generation_failed` and `internal_error` map to `provider_failed`, and neither is
      resubmitted.
- [ ] A `generation.processing` applied after `generation.succeeded` or `generation.failed` changes
      nothing.
- [ ] Applying the same succeeded or failed update twice leaves one `release` and at most one `charge`.
- [ ] A webhook applied to a `pending` generation moves it forward and stores the provider's id; the
      submission job's later response then changes nothing.
- [ ] The webhook job, the polling job and the submission job all change a generation only through the
      one action.
- [ ] When the charge exceeds the hold, the full charge is recorded, a warning is logged with the
      generation, hold, charge and difference, and the balance may be negative.
- [ ] A user whose balance is negative cannot submit a generation.
- [ ] Generations whose charge exceeds their hold, and users with a negative balance, can be listed
      from the ledger alone.

Polling

- [ ] The polling job is scheduled every minute.
- [ ] It polls only `queued` and `processing` generations not updated or polled within the configured
      age, at most the configured number per run, least recently updated or polled first.
- [ ] A generation polled with no change is not picked again before others that waited longer, so a
      stuck generation cannot block the rest.
- [ ] A generation whose webhooks never arrive reaches the provider's final status, with the same
      records as by webhook.
- [ ] A `429` while polling ends the run without error.
- [ ] A `503` or a network error while polling one generation skips it until the next run, and the
      run continues with the others.
- [ ] A `404`, or a generation not final an hour after submission, logs an error and leaves the
      generation and its hold unchanged.

Reconciliation

- [ ] For the generations left on the deployed app, the sum of provider costs per UTC completion day
      equals `cost_usd` from `GET /v1/usage` for that day.

## Out of scope

- **Showing status, charges and images to the user, live**: the `history` spec.
- **Submission, the rate check and `price_changed`**: the `generating` spec.
- **The operator report and its "uncovered charges" section**: the `operator-report` spec; this spec
  only makes the data findable.
- **Downloading images before their URL expires**: not done (ADR 0012).
- **Resubmitting failed generations automatically**: not done; the user resubmits.
- **Replaying unapplied webhook events by hand**: polling covers them; no admin tool.
- **`[slow]` and `[succeed]`**: need no handling of their own. A `[slow]` generation stays
  `processing` for 60 to 90 seconds and is covered by the normal path or by polling; `[succeed]` takes
  the normal path.
- **Pixelforge's `List generations` endpoint**: not used; polling fetches one generation at a time.

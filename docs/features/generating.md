# Generating

- Status: approved
- Sources:
  - Brief: [What to build](../spec/assessment.md#what-to-build) (Generating, Pricing),
    [What we look at](../spec/assessment.md#what-we-look-at),
    [Stretch goals](../spec/assessment.md#stretch-goals-bonus-points) (Balances)
  - Pixelforge: [Errors](../spec/pixelforge-api.md#errors),
    [Rate limits and quotas](../spec/pixelforge-api.md#rate-limits-and-quotas),
    [Idempotency](../spec/pixelforge-api.md#idempotency),
    [Create a generation](../spec/pixelforge-api.md#create-a-generation),
    [Pricing and token usage](../spec/pixelforge-api.md#pricing-and-token-usage),
    [Testing](../spec/pixelforge-api.md#testing)
  - ADRs: 0005, 0006, 0007, 0008, 0009, 0011, 0014, 0017, 0020, 0022, 0023, 0024

## Goal

A signed-in user writes a prompt, picks a size, a quality and up to 4 reference images, sees the
estimate before submitting, and submits. The app holds the estimate's maximum from their balance and
sends the generation to the provider in the background, absorbing the provider's limits and
overloads. This meets the brief's **Generating** requirement and the up-front half of **Pricing**,
and blocks generations the user cannot afford (**Balances**).

What happens after the provider accepts the generation (webhooks, polling, the charge) is the
`generation-updates` spec; how generations are listed is the `history` spec.

## Behaviour

### The generate page

Only signed-in users can open it. It shows:

- **Prompt**: a required text area, at most 4,000 characters, with a live counter (`312 / 4000`).
- **Size**: every size, `1024x1024` selected by default (ADR 0023).
- **Quality**: every quality, `medium` selected by default (ADR 0023).
- **Reference images**: none at first; an "Add reference image" button adds a URL field, up to 4;
  each field has a remove button. Each value must be an `http://` or `https://` URL. The same URL may
  appear twice; it is billed and estimated twice.
- **Balance**: the user's balance, in the 4-decimal format with the exact amount on hover (ADR 0007).
- **Estimate**: the range of possible charges, `$0.0123 – $0.0456`, each end exact on hover, with the
  note "You're charged the exact amount when the image is ready. Failed generations are free."
- **Generate** button.

### The estimate

- It is computed on the server from the provider's current pricing (ADR 0011) and refreshed,
  debounced, whenever the prompt, size, quality or number of reference images changes. The frontend
  holds no pricing logic.
- Its ends are user charges, markup included: each is the provider cost at that end, × 1.25, rounded
  up to a whole nanodollar (ADR 0005).
- Token counts at each end, from the provider's pricing: text tokens are `ceil(characters / 4)`
  counting Unicode characters, taken −15% (rounded down) for the minimum and +15% (rounded up) for the
  maximum (ADR 0024); image tokens are exact per reference image; output tokens are exact for the size and
  quality; reasoning tokens take the quality's minimum and maximum.
- The maximum is the amount held on submission for the same input (ADR 0006, ADR 0011).

### States of the form

| State | What the user sees | Generate button |
| --- | --- | --- |
| Ready | Estimate and balance | Enabled |
| Estimating | The previous estimate stays until the new one arrives | Enabled |
| Not enough credits | "Not enough credits: this can cost up to $X and your balance is $Y", with a "Buy credits" link to the page the `balances` spec builds | Disabled |
| Price unavailable | "Price unavailable right now" in place of the estimate; the page asks for the estimate again every 10 seconds until one arrives, then returns to Ready on its own | Disabled |
| Submitting | The form as it was | Disabled until the server answers |
| Invalid | The message next to each invalid field | Enabled |

### After submitting

The user is redirected to the history page with the message "Generation submitted", and the new
generation is first in the list, in `pending`. It moves to `queued` once the provider accepts it, or
to `failed` with one of these messages (ADR 0008):

| Failure reason | Message |
| --- | --- |
| `prompt_rejected` | "Your prompt was rejected by the image provider's content policy. You were not charged." |
| `provider_failed` | "The image could not be generated. You were not charged. Please try again." |
| `provider_unavailable` | "The image service was busy or unavailable, so we gave up. You were not charged. Please try again." |

`provider_failed` is defined here and reached in the `generation-updates` spec.

## Flows

### Normal submission

1. The user fills the form; the estimate updates as they type.
2. They press Generate. The server validates the input (ADR 0023) and computes the estimate from the
   current pricing.
3. In one transaction, locking the user's row: the server checks that the balance covers the
   estimate's maximum, creates the generation in `pending`, inserts a `hold` of minus that maximum,
   and dispatches the submission job (ADR 0006, ADR 0009).
4. The user is redirected to the history page and sees the `pending` generation.
5. The job sends the generation to the provider with the generation's UUID as idempotency key and the
   generation's id in the metadata (ADR 0009).
6. The provider accepts it. The job applies the response as a provider update through the shared
   action (ADR 0010): the generation moves to `queued` and stores the provider's id.

### The provider answers with a later status

A retried create with the same idempotency key may return the generation as it is now, already
`processing` or final, and a webhook may arrive before the create returns. The job applies whatever
the response says through the same action, and statuses only move forward (ADR 0008), so neither case
moves a generation back or applies anything twice.

### Invalid input

The server answers with an error next to each invalid field. No generation, hold or job is created.

### Not enough credits

1. The estimate's maximum is above the balance: the form shows the "Not enough credits" state and the
   Generate button is disabled.
2. If the balance drops between the estimate and the submission (another tab), the server's check in
   step 3 fails: the form shows the same message as a form error, and nothing is created.

### Pricing unavailable

1. The provider's pricing cannot be fetched and none is cached: the estimate request fails, the form
   shows "Price unavailable right now" in place of the estimate, and Generate is disabled.
2. The page asks for the estimate again every 10 seconds, with no action from the user, as long as
   the price stays unavailable. A change to the form also asks again at once.
3. As soon as an estimate arrives, the range appears and Generate is enabled again.
4. If the price was shown but pricing fails when the user presses Generate, nothing is created or
   held. The form keeps what the user typed, shows the error "We couldn't confirm the price, so
   nothing was submitted.", and enters the Price unavailable state from step 1.

### Prompt rejected (`[blocked]`)

1. The provider answers `400 content_policy_violation`.
2. The job fails the generation with `prompt_rejected` and inserts a `release` equal to the hold. The
   job is not retried.
3. The user sees the generation as failed, with the `prompt_rejected` message, and their balance is
   back where it was.

### Rate limited, concurrency limited, overloaded or unreachable (`[overloaded]`)

1. The provider answers `429 rate_limit_exceeded`, `429 concurrency_limit_exceeded` or
   `503 overloaded`, or the request fails with a network error or timeout.
2. The job waits the longer of `Retry-After` and the backoff for that attempt (5 s, doubling, at most
   60 s), then retries with the same idempotency key (ADR 0022). The generation stays `pending`.
3. If a later attempt is accepted, the normal flow continues from step 6. A request that timed out
   after the provider created the generation is answered on retry as an idempotent replay: still one
   generation, billed once.
4. After 8 attempts (from configuration), the job fails the generation with `provider_unavailable`
   and inserts a `release` equal to the hold. With `[overloaded]` in the prompt this always happens,
   about 5 minutes after submitting.

### Errors waiting cannot fix

1. The provider answers `429 daily_limit_exceeded`, `401 invalid_api_key`, `422 validation_failed` or
   `409 idempotency_key_reused`.
2. The job fails the generation at once with `provider_unavailable`, inserts a `release` equal to the
   hold, and logs the provider's answer as an error (ADR 0022).

### The job runs twice

A worker restart can run the job again. The second run sends the same idempotency key, so the
provider returns the same generation, and the shared action ignores what it already applied: one
generation, one hold, at most one release.

## Acceptance criteria

Form and estimate

- [ ] A guest opening the generate page is redirected to sign in.
- [ ] The form lists every size and every quality, with `1024x1024` and `medium` selected.
- [ ] The form accepts 0 to 4 reference image URLs, adds and removes fields, and offers no fifth.
- [ ] The form has no field for the provider's metadata.
- [ ] The prompt counter shows the number of characters typed out of 4,000.
- [ ] For a 400-character prompt, `high`, `1536x1024` and 2 reference images at the published rates,
      the estimate's maximum is the charge for a provider cost of $0.373875000, i.e. $0.467343750.
- [ ] The estimate's minimum uses −15% text tokens (rounded down) and the quality's minimum reasoning
      tokens; its maximum uses +15% text tokens (rounded up) and the quality's maximum reasoning
      tokens.
- [ ] A duplicated reference image URL counts twice in the estimate.
- [ ] Rates come from the provider's pricing endpoint and are cached for the configured time; a
      change of rates is reflected after the cache expires.
- [ ] Amounts on the page use 4 decimals and show the exact amount on hover.
- [ ] When the pricing cannot be fetched, the page shows "Price unavailable right now" and Generate is
      disabled.
- [ ] While the price is unavailable, the page asks for the estimate every 10 seconds, and shows the
      range and enables Generate as soon as one arrives, without any action from the user.
- [ ] When pricing fails at submission, no generation, hold or job is created, the form keeps its
      values, shows "We couldn't confirm the price, so nothing was submitted.", and enters the Price
      unavailable state.

Validation

- [ ] A missing prompt, a prompt over 4,000 characters, an unknown size or quality, more than 4
      reference images, or a reference image that is not an http(s) URL is rejected with a message on
      that field, and creates no generation, hold or job.
- [ ] A prompt of exactly 4,000 Unicode characters is accepted.

Balance and hold

- [ ] When the balance is below the estimate's maximum, Generate is disabled and the page shows the
      "Not enough credits" message with a "Buy credits" link.
- [ ] A submission whose maximum exceeds the balance at the server is refused with that message and
      creates nothing.
- [ ] A user with no ledger rows cannot submit a generation.
- [ ] A valid submission creates one generation in `pending`, one `hold` row of minus the estimate's
      maximum for the same input, and one queued submission job, all in one transaction.
- [ ] Two concurrent submissions that together exceed the balance cannot both create a hold.
- [ ] After submitting, the user is on the history page with "Generation submitted" and the new
      generation first.

Submission job

- [ ] No web request calls the provider to create a generation.
- [ ] The create request carries the generation's UUID as `Idempotency-Key`, the same on every
      attempt, and the generation's id in `metadata`.
- [ ] An accepted create moves the generation to `queued` and stores the provider's id.
- [ ] A create response with a later status (an idempotent replay) is applied through the shared
      action and never moves the generation back.
- [ ] `[blocked]`: the generation fails with `prompt_rejected` after one attempt, gets one `release`
      equal to its hold and no `charge`, and the user's balance equals the balance before submitting.
- [ ] `429 rate_limit_exceeded`, `429 concurrency_limit_exceeded`, `503 overloaded` and a network
      error are retried after the longer of `Retry-After` and the attempt's backoff, with the
      generation staying `pending`.
- [ ] A retry that is then accepted leaves one generation and one hold.
- [ ] `[overloaded]`: after the configured number of attempts (default 8) the generation fails with
      `provider_unavailable` and gets one `release` equal to its hold.
- [ ] `429 daily_limit_exceeded`, `401`, `422` and `409` fail the generation with
      `provider_unavailable` after one attempt, release its hold, and log an error.
- [ ] Running the job twice for the same generation creates no second provider generation, hold or
      release.
- [ ] Each failure reason shows its message from the table above.

## Out of scope

- **Webhooks, polling, the charge and `provider_failed`** (`[fail]`, `[slow]`, `[duplicate]`,
  `[succeed]`, out-of-order events, and a charge above the hold if the provider's rates change between
  the estimate and the creation): the `generation-updates` spec.
- **Listing generations, live status, estimate vs. charge, images**: the `history` spec. This spec only
  needs the history page to exist and list the signed-in user's own generations, newest first.
- **Buying credits and showing the balance outside this page**: the `balances` spec, built before the
  first deploy. Until then, tests create `purchase` rows directly.
- **The operator report**: the `operator-report` spec.
- **Cancelling a `pending` generation, editing or resubmitting a generation**: not built; nothing in
  the brief asks for it.
- **Checking that a reference image URL points to an image**: Pixelforge does not download them,
  so only the URL's form is checked.
- **Pixelforge's `List generations` endpoint**: not used; we fetch one generation at a time.

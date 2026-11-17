# History

- Status: approved
- Sources:
  - Brief: [What to build](../spec/assessment.md#what-to-build) (History),
    [What we look at](../spec/assessment.md#what-we-look-at)
  - Pixelforge: [Lifecycle](../spec/pixelforge-api.md#lifecycle),
    [Images](../spec/pixelforge-api.md#images), [Testing](../spec/pixelforge-api.md#testing)
  - ADRs: 0007, 0012, 0017, 0020, 0025, 0028
  - Specs: [generating](generating.md), [generation-updates](generation-updates.md)

## Goal

A signed-in user sees all of their own generations, newest first, with live status, the estimate,
the charge and the image, and understands at every step what is happening and what went wrong. This
meets the brief's **History** requirement.

## Behaviour

### Navigation

- The starter kit's Dashboard page is removed. After signing in, or opening the app's home while
  signed in, the user lands on **Generate**.
- The sidebar shows **Generate** and **History**. Other entries come with their own specs (the
  balance with `balances`, the report with `operator-report`).
- Guests opening History are redirected to sign in.

### The history page

A responsive grid of cards, one per generation of the signed-in user, newest first, 20 per page, with
previous and next links. Coming from Generate, the message "Generation submitted" shows above it.

With no generations: "No generations yet", and a link to Generate.

### A card

| Part | Shows |
| --- | --- |
| Image | `succeeded` and before its expiry: the image; clicking opens it full size in a new tab. `succeeded` after its expiry: "Image expired" (ADR 0012). An image that fails to load before its expiry: "Image unavailable". Any other status: a placeholder for that status. |
| Status | A badge with the status, and a one-line hint (below). |
| Prompt | Cut to 3 lines; clicking shows the rest. |
| Options | Size, quality, and the number of reference images. |
| Cost | "Estimate $0.0123 – $0.0456" always. Then "Charged $0.0345" once `succeeded`, or "Not charged" once `failed`. Every amount in the 4-decimal format with the exact amount on hover (ADR 0007). |
| Created | Relative time ("3 minutes ago"), with the exact date and time on hover, in the user's local time. |

| Status | Hint |
| --- | --- |
| `pending` | "Waiting for the image service" |
| `queued` | "In the provider's queue" |
| `processing` | "Generating…" |
| `succeeded` | None |
| `failed` | The failure reason's message, from the `generating` spec (ADR 0025) |

The user never sees the provider cost, token counts or the provider's own error codes. There is no
page for a single generation.

### Live status

While any generation on the current page is not final, the page reloads its data from our server
every 3 seconds; once all are final it stops (ADR 0028). A reload changes the cards in place: no
flicker, no scroll jump, and the page number stays. The "Image expired" state also appears without a
reload once a card's expiry time passes.

## Flows

### Normal path

1. The user submits on Generate and lands on History with "Generation submitted". The new generation
   is first, `pending`, with its estimate.
2. The page reloads every 3 seconds. The card moves through `queued` and `processing` as the updates
   arrive (`generation-updates` spec).
3. The card shows `succeeded`, the image and "Charged $X". Nothing on the page is running, so the
   reloads stop.

### Failed generation (`[fail]`, `[blocked]`, `[overloaded]`, rates changed)

1. The card reaches `failed`. Its hint is the message for its failure reason, and its cost shows "Not
   charged".
2. If it was the last running generation on the page, the reloads stop.

### Slow generation (`[slow]`)

The card stays `processing` for 60 to 90 seconds while the page keeps reloading, then finishes as in
the normal path.

### Waiting on the provider's limits

A generation the submission job is retrying stays `pending` with "Waiting for the image service" for
up to about 5 minutes (ADR 0022), then moves on or fails with `provider_unavailable`.

### Expired image

1. A succeeded generation's expiry time passes, on the page or before it was opened.
2. The card shows "Image expired" instead of requesting the image. The charge and the rest of the card
   stay.

### Many generations

1. The user has more than 20 generations. The page shows the newest 20 and a next link.
2. On an older page, reloads run only if a generation on that page is not final.

## Acceptance criteria

Access and navigation

- [ ] A guest opening History is redirected to sign in.
- [ ] A user sees only their own generations; another user's generation never appears on their
      history, on any page.
- [ ] The Dashboard page and its route no longer exist; signing in lands on Generate.
- [ ] The sidebar has Generate and History.

The list

- [ ] Generations are listed newest first, 20 per page, with previous and next links.
- [ ] With no generations, the page shows "No generations yet" and a link to Generate.
- [ ] After submitting, the page shows "Generation submitted" and the new generation first, `pending`.

Cards

- [ ] Each card shows the status badge, the status's hint, the prompt cut to 3 lines and expandable,
      the size, the quality, the number of reference images and the relative creation time with the
      exact time on hover.
- [ ] Every card shows its estimate range; a succeeded card also shows its charge; a failed card shows
      "Not charged". Amounts use 4 decimals with the exact amount on hover.
- [ ] A `[fail]` generation's card shows the `provider_failed` message; a `[blocked]` one the
      `prompt_rejected` message; an `[overloaded]` one the `provider_unavailable` message.
- [ ] A succeeded card before its expiry shows the image, which opens full size in a new tab.
- [ ] A succeeded card after its expiry shows "Image expired" and does not request the image.
- [ ] An image that fails to load before its expiry shows "Image unavailable".
- [ ] No page shows a provider cost, a token count or a provider error code.

Live status

- [ ] While a generation on the current page is not final, the page reloads every 3 seconds, and the
      card's status changes without the user doing anything.
- [ ] When every generation on the current page is final, the page stops reloading.
- [ ] A reload only reads our database; it never calls the provider.
- [ ] A reload keeps the current page and scroll position.

## Out of scope

- **Push updates (websockets, Server-Sent Events)**: not built (ADR 0028).
- **Storing images after their URL expires**: not built (ADR 0012).
- **Filtering, searching or deleting generations; a page per generation**: not built; the brief does
  not ask for them.
- **Showing the balance, buying credits**: the `balances` spec.
- **Resubmitting a failed generation in one click**: not built; the user submits again on Generate.
- **Refreshing an image URL from the provider**: a fresh URL expires at the same time
  (`docs/spec/pixelforge-api.md#images`), so it would not help.

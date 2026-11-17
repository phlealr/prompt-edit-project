# Glossary

The words this project uses for its domain. Use each term exactly as written here, in code, tests,
tickets, specs and conversation; the _Avoid_ list names the synonyms we do not use. A concept missing
from this list is either new language to reconsider or a gap to record with `record-decision`.

## Generations

- **Generation**: one request by a user for one image, from submission to its final status. _Avoid_:
  job, request, image, task (a "job" is a queued job; an "image" is the generation's result).
  _Source_: ADR 0025.
- **Status**: where a generation is in its lifecycle: `pending`, `queued`, `processing`,
  `succeeded` or `failed`; it only moves forward. _Avoid_: state (for this field), stage. _Source_:
  ADR 0025.
- **Pending**: the status of a generation saved by us and not yet accepted by the provider. _Avoid_:
  submitting, new, draft. _Source_: ADR 0025.
- **Final status**: `succeeded` or `failed`; a generation never leaves it. _Avoid_: done, complete,
  terminal. _Source_: ADR 0025.
- **Failure reason**: why a generation failed, in our own terms: `prompt_rejected`,
  `provider_failed`, `provider_unavailable` or `price_changed`. _Avoid_: error code (that is the
  provider's). _Source_: ADR 0025.
- **Size**: the image dimensions a user picks: `1024x1024`, `1536x1024` or `1024x1536`. _Avoid_:
  resolution, dimensions, format. _Source_: ADR 0023.
- **Quality**: the detail level a user picks: `low`, `medium` or `high`. _Avoid_: tier, level, mode.
  _Source_: ADR 0023.
- **Reference image**: an http(s) image URL a user adds to guide the style, at most 4 per generation.
  _Avoid_: style image, attachment, input image. _Source_: ADR 0023.

## Provider

- **Provider**: an external image-generation service the app sends generations to; Pixelforge is the
  current one, behind the provider interface. In code it is `ImageProvider`, because Laravel's
  `app/Providers/` already means service providers. _Avoid_: vendor, API, Pixelforge (except for the
  Pixelforge implementation itself). _Source_: ADR 0014, ADR 0020.
- **Provider update**: the provider's view of a generation at one moment (status, result, cost),
  whether it came from a submission response, a webhook or a poll; applied to the generation by one
  action. _Avoid_: callback, sync, refresh. _Source_: ADR 0010.
- **Rates**: the provider's price of 1M tokens of each kind (input text, input image, output,
  reasoning), as exact amounts; a generation keeps the rates its hold used. _Avoid_: pricing, prices,
  tariff (the provider's pricing is the endpoint the rates come from). _Source_: ADR 0026, ADR 0035.
- **Webhook event**: one notification the provider posted to us, stored once by its event id.
  _Avoid_: callback, hook, notification. _Source_: ADR 0010.
- **Reload**: the history page fetching its generations again from our own server, on an interval,
  while any of them is not final. _Avoid_: poll (that is fetching from the provider), refresh, live
  update. _Source_: ADR 0028.
- **Poll**: fetching a generation's current state from the provider because its webhooks may be lost.
  _Avoid_: sync, refresh, check. _Source_: ADR 0010.

## Money

- **Nanodollar**: the unit of every stored amount: one billionth of a US dollar. _Avoid_: cents,
  micros, units. _Source_: ADR 0005.
- **Credits**: the user's money in the app; one credit is one US dollar. _Avoid_: points, tokens
  (tokens are the provider's pricing units). _Source_: ADR 0006.
- **Provider cost**: what the provider charges us for a succeeded generation. _Avoid_: price, charge,
  fee. _Source_: ADR 0005.
- **Markup**: the 25% added to the provider cost. _Avoid_: margin, fee, commission. _Source_:
  ADR 0005.
- **Charge**: what the user pays for a succeeded generation: provider cost plus markup, rounded up to
  a whole nanodollar. Also the ledger row recording it. _Avoid_: price, cost, bill, fee. _Source_:
  ADR 0005, ADR 0006.
- **Estimate**: the range of possible charges shown before submitting, from minimum to maximum.
  _Avoid_: quote, preview, price. _Source_: ADR 0011.
- **Ledger**: the append-only list of every credit movement (`credit_transactions`). _Avoid_:
  transactions table, wallet, history. _Source_: ADR 0006.
- **Purchase**: a ledger row adding credits the user bought. _Avoid_: top-up, deposit, recharge.
  _Source_: ADR 0006.
- **Hold**: a ledger row reserving the estimate's maximum when a generation is submitted. _Avoid_:
  reservation, authorization, block. _Source_: ADR 0006.
- **Release**: a ledger row giving a hold back when the generation reaches a final status. _Avoid_:
  refund, unhold, cancel. _Source_: ADR 0006.
- **Balance**: the sum of a user's ledger rows. _Avoid_: wallet, funds, account. _Source_: ADR 0006.
- **Uncovered charge**: the part of a charge above its generation's hold, possible only when the
  provider's rates rose after the rate check; it can take a balance below zero. _Avoid_: overdraft,
  debt, loss. _Source_: ADR 0026.

## People and code

- **User**: a person who signs in and generates images. _Avoid_: customer, client, account. _Source_:
  ADR 0017.
- **Operator**: a user with `is_admin` set, who can see the operator report. _Avoid_: admin (except
  for the `is_admin` column and the command), staff. _Source_: ADR 0016.
- **Operator report**: the page showing spend, provider cost and profit per UTC completion day and per
  user, reconciled with the provider's usage. _Avoid_: dashboard, admin page, analytics. _Source_:
  ADR 0016.
- **Action**: a class in `app/Actions/<Area>/` that holds one piece of business logic behind one
  public `handle()` method, called by controllers, jobs and commands. _Avoid_: use case, service,
  handler, manager. _Source_: ADR 0020.
- **Area**: one of the groups the code is organised by in every layer: `Generations`, `Credits`,
  `Reports`. _Avoid_: module, domain, context. _Source_: ADR 0020.

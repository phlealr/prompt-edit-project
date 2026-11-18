# 04. Polling

- Status: done
- Blocked by: 03
- Spec: docs/features/generation-updates.md

## What to build

Every minute a scheduled job fetches from the provider the generations in `queued` or `processing`
that have gone quiet, at most the configured number, least recently updated or polled first, and
applies what it gets through the one action. Limits and errors never fail a generation the provider
accepted (ADR 0010, ADR 0027).

## Acceptance criteria

- [x] The polling job is scheduled every minute.
- [x] It polls only `queued` and `processing` generations not updated or polled within the
      configured age, at most the configured number per run, least recently updated or polled first.
- [x] A generation polled with no change is not picked again before others that waited longer, so a
      stuck generation cannot block the rest.
- [x] A generation whose webhooks never arrive reaches the provider's final status, with the same
      records as by webhook.
- [x] A `429` while polling ends the run without error.
- [x] A `503` or a network error while polling one generation skips it until the next run, and the
      run continues with the others.
- [x] A `404`, or a generation not final an hour after submission, logs an error and leaves the
      generation and its hold unchanged.
- [x] The webhook job, the polling job and the submission job all change a generation only through
      the one action.
- [x] A `pending` generation that may exist at the provider and whose submission job gave up is
      re-sent with its idempotency key and without the rate check, and the answer is applied through
      the one action (ADR 0042).

## Notes

- `PollGenerationsJob` runs every minute (`routes/console.php`); `composer run dev` now also starts
  `schedule:work` (registered in `AppServiceProvider`), since `artisan dev` runs no scheduler.
  Production needs the scheduler running too.
- `PollGenerations` marks each generation polled before asking the provider, without touching
  `updated_at`, so a generation that keeps failing goes to the back of the line; a 429 ends the run,
  an unreachable or unreadable answer skips that generation, a 404 or anything unexpected is logged.
- The due generations are sorted by the later of `updated_at` and `polled_at` in PHP rather than with
  PostgreSQL's `GREATEST` (which would need an ADR, ADR 0014); few are ever waiting at once.
- ADR 0042: the submission job marks `submission_gave_up_at`; polling re-sends those generations
  through `SendGeneration` (same key, no rate check). A refusal of a generation that may exist at the
  provider is retried rather than failed.
- An unexpected 4xx while polling (for example a 401) is logged as an error and skipped each run.

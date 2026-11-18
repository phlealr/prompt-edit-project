# Prompt Edit Project

Generate images from a prompt through [Pixelforge](https://pixelforge.promptedit.dev/docs), paying
Pixelforge's exact cost plus a markup (25% by default). Built with Laravel 13, Inertia, Vue 3 and
PostgreSQL.

**Live app:** https://prompt-edit-project-production-wggcbu.laravel.cloud

## Signing in

| Account | Email | Password |
| --- | --- | --- |
| Operator (can open the Operator report) | `operator@promptedit.test` | `Review-pmBOtj2rLEsj!9a` |

You can also register your own account from the home page; a new account starts with $0.00 and can
buy credits right away.

## Using the app

1. **Buy credits.** Open **Credits** in the sidebar (or use the buy form on Generate) and buy a whole
   number of dollars, from $1 to $1,000. It's a fake purchase: no payment is taken.
2. **Generate.** Open **Generate**, write a prompt, pick a size and a quality, and optionally add up
   to 4 reference image URLs. The estimate updates as you type: it's the range the image can cost,
   and its maximum is held from your balance when you submit.
3. **Follow it on History.** The card moves from `pending` (saved, not yet sent) to `queued`,
   `processing`, and `succeeded` with the image and the exact charge, or `failed` with the reason and
   "Not charged". The page refreshes itself while anything is running.
4. **Check the money on Credits.** Every purchase and charge is listed; the amount held for
   generations still running is shown under the balance. Purchases and charges, less the held amount,
   add up to the balance.
5. **Operator report** (operator account only). For any range of UTC days: generations, user spend,
   provider cost and profit per day and per user, Pixelforge's own cost per day with the difference
   from ours (highlighted when not zero), and any charge that went above its hold.

Amounts show 4 decimals; hover any amount to see it exactly (9 decimals). The operator report always
shows 9 decimals, to compare with Pixelforge's usage report.

### Trying Pixelforge's failure modes

Put a keyword anywhere in a prompt (from Pixelforge's docs):

| Keyword | What you'll see |
| --- | --- |
| `[succeed]` | Succeeds every time. |
| `[fail]` | Fails while processing: "The image could not be generated. You were not charged." |
| `[blocked]` | Rejected at once: "Your prompt was rejected… You were not charged." |
| `[overloaded]` | Retried with backoff for about 5 minutes, then "The image service was busy or unavailable, so we gave up. You were not charged." |
| `[slow]` | Takes 60–90 seconds. |
| `[duplicate]` | Every webhook arrives twice; it is still charged once. |

## How it handles money and Pixelforge

- Every amount is an integer number of nanodollars (a billionth of a dollar); there are no floats.
- The charge is the provider cost plus the markup, rounded **up** once to a whole nanodollar, so a
  user never pays less than cost plus markup.
- Credits are an append-only ledger: a submission holds the estimate's maximum; a success releases
  it and charges the exact amount; a failure releases it and charges nothing. The database allows
  one hold, one release and one charge per generation.
- Generations are sent from a queued job with the generation's UUID as idempotency key, so retries
  never create or bill a second image. Rate limits, overloads and network errors are retried with
  backoff and `Retry-After`.
- Webhooks are verified (signature on the raw body, 5-minute window), recorded once by event id and
  applied by a job; statuses only move forward, so duplicates and out-of-order events change nothing.
- A scheduled job polls Pixelforge every minute for generations whose webhooks went missing.

The reasoning behind each choice is in [`docs/adr/`](docs/adr/); the feature specs are in
[`docs/features/`](docs/features/).

## Running it locally

Requirements: PHP 8.3+ (with `pdo_pgsql`, `pcntl`, `posix`), Composer, Node 20+, Docker.

```bash
docker compose up -d                 # PostgreSQL on port 5433
composer setup                       # installs, creates .env, migrates, builds
php artisan db:seed --class=OperatorSeeder
composer run dev                     # app, queue worker, scheduler and Vite
```

Then set your Pixelforge key in `.env` (`PIXELFORGE_API_KEY`, and `PIXELFORGE_WEBHOOK_SECRET` if a
tunnel forwards webhooks to `/webhooks/pixelforge`). Without webhooks, polling finishes generations
within about 2 minutes.

Useful settings, all optional:

| Variable | Default | What it does |
| --- | --- | --- |
| `MARKUP_PERCENT` | `25` | The markup on the provider cost, a whole number from 0 to 1000. |
| `OPERATOR_EMAIL`, `OPERATOR_PASSWORD` | the login above | The account `OperatorSeeder` creates. |
| `PIXELFORGE_PRICING_CACHE_SECONDS` | `300` | How long pricing is cached for the estimate. |
| `PIXELFORGE_SUBMIT_MAX_ATTEMPTS` | `8` | Attempts before a busy submission gives up. |

Any user can be made an operator with `php artisan user:make-admin {email}`.

## Checks

```bash
composer check   # formatting, lint, vue-tsc, Pint, Larastan level 7, and the Pest suite
```

## Deploying (Laravel Cloud)

- A PostgreSQL database, `QUEUE_CONNECTION=database`, a queue worker on the `database` connection,
  and the scheduler enabled, with the App cluster kept awake (no scale to zero).
- Deploy commands: `php artisan migrate --force` and
  `php artisan db:seed --class=OperatorSeeder --force`.
- Environment: `PIXELFORGE_API_KEY` and `PIXELFORGE_WEBHOOK_SECRET` from a Pixelforge key used only by
  this deployment, whose webhook URL is `https://<app>/webhooks/pixelforge`.

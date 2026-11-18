# Coding standards

The rules every change follows, across layers. Each rule cites the ADR that decided it; read the ADR
for the reason. Rules for one layer live in that folder's `CLAUDE.md`; framework guidelines for the
installed versions are in [`docs/agents/laravel.md`](docs/agents/laravel.md). When a rule here and an
ADR disagree, the ADR wins and this file is fixed.

## Checks

`docker compose up -d` must be running: development and tests use PostgreSQL on port 5433 (ADR 0003,
ADR 0019).

| Command | Runs | When |
| --- | --- | --- |
| `composer check` | `vp check`, `vue-tsc`, Pint (test mode), Larastan level 7, Pest | Before a ticket is done. It must pass (ADR 0021). |
| `php artisan test --compact <file or --filter=name>` | The tests that cover the change | During the red-green cycle (`tdd` skill). |
| `vendor/bin/pint --dirty` | Formats changed PHP | Runs automatically after every PHP edit (hook). |

A failing check is fixed, never skipped, silenced, or added to a baseline (ADR 0015, ADR 0021).

## Where code goes (ADR 0020)

The structure is layer-first; every layer uses the same **areas**: `Generations`, `Credits`, `Reports`.

| Kind | Place |
| --- | --- |
| Business logic | `app/Actions/<Area>/<VerbNoun>.php`, one public `handle()` method |
| HTTP entry points | `app/Http/Controllers/<Area>/<VerbNoun>Controller.php`, single `__invoke` |
| Validation | `app/Http/Requests/<Area>/<VerbNoun>Request.php` |
| Queued entry points | `app/Jobs/<Area>/` |
| Provider boundary | `app/ImageProviders/` (interface and shared types), `Exceptions/`, `Pixelforge/` |
| Money | `app/ValueObjects/Money.php` |
| Data passed between our classes and to pages | `app/Data/<Area>/`, readonly classes (ADR 0041) |
| Statuses, failure reasons, sizes, qualities | `app/Enums/` |
| Models | `app/Models/` |
| Pages | `resources/js/pages/<area>/` |
| Tests | `tests/Feature/<Area>/`, `tests/Unit/<Area>/`, multi-process tests in `tests/Concurrency/<Area>/` (ADR 0039), architecture tests in `tests/Arch/` |

Create classes with `php artisan make:*` and move nothing out of the place the table gives.

## Layers (ADR 0013, ADR 0020)

- Controllers and jobs are entry points: a controller validates with its Form Request, calls one
  action and returns a response; a job's `handle()` calls one action. Neither holds a query, a
  calculation or a provider call.
- Actions hold all business logic and are called the same way from a controller, a job or a command.
- One action changes a generation's status and writes its charge; every path (submission, webhook,
  poll) calls it (ADR 0010).
- Configuration uses Laravel 13 PHP attributes wherever one exists: `#[Tries]`, `#[Backoff]`,
  `#[Timeout]` on jobs; `#[Middleware]`, `#[Authorize]` on controllers; `#[Fillable]`, `#[Hidden]`,
  `#[Table]` on models.

## Data (ADR 0041)

- Data is a typed object: a readonly class with named, typed properties. No parameter, return value,
  property, cache value or page prop is an array shape (`array{…}`) or an associative array carrying
  data.
- Arrays are only lists (`list<T>`) or what the framework requires: validation rules, casts and
  multi-column attribute setters, config, the outer map of `Inertia::render()` props,
  `jsonSerialize()`, request payloads, factory attributes.
- A provider's decoded JSON is read once, where it arrives, and mapped straight into typed objects.

## Boundaries (ADR 0014)

- Only `app/ImageProviders/Pixelforge/` knows Pixelforge: its URLs, headers, error codes, signature
  format, statuses, ids and token pricing. Code outside `app/ImageProviders/` uses Laravel's HTTP
  client for nothing.
- Everything crossing the provider interface is our own type: our enums, our exceptions, `Money`.
- Provider settings (base URL, API key, webhook secret) live under `services.pixelforge` in
  `config/services.php`, read from `.env`; no secret is committed (ADR 0019).
- Queue, database, files and cache are used only through Laravel's queue API, Eloquent and the query
  builder, `Storage` and `Cache`. PostgreSQL-specific SQL needs an ADR.
- Our own classes get an interface only when a second implementation exists.
- Every generation records its provider; code acting on an existing generation gets that provider
  from `ImageProviders::for()`, never from the default binding (ADR 0044).

## Money (ADR 0005, ADR 0006, ADR 0007, ADR 0026)

- Every amount is an integer number of nanodollars, in a `bigint` column, handled as `Money`. No
  `float` holds money anywhere: PHP, SQL, casts or JavaScript.
- Provider decimal strings become nanodollars by exact string parsing, never through a float.
- The markup and its rounding happen only in `Money`: charge = ceil(cost × 5 / 4), integer arithmetic,
  once, on the final amount. A charge is never capped at its hold; one above it logs a warning.
- Money reaches the frontend already formatted by `Money` (4 decimals for users, the exact 9 decimals
  for operators, the exact amount on hover). The frontend does no money arithmetic.
- Credit movements are rows inserted in `credit_transactions` (`purchase`, `hold`, `release`,
  `charge`), never updated or deleted. The database allows one row per generation and type.
- A balance is the sum of the user's ledger rows. The balance check and the hold are written in one
  transaction that locks the user's row.

## Generations (ADR 0009, ADR 0010, ADR 0025, ADR 0026, ADR 0027)

- Statuses move forward only: `pending → queued → processing → succeeded | failed`. A backwards or
  out-of-final update is ignored without error.
- `failure_reason` (`prompt_rejected`, `provider_failed`, `provider_unavailable`, `price_changed`) is
  set exactly when the status is `failed`.
- The provider is never called from a web request to create a generation; a queued job does it, with
  the generation's UUID as the idempotency key and its id in the provider metadata.
- Creating a generation, its hold and its job happen in one transaction.
- A generation stores the rates its hold used. Before each create attempt (except after a network
  error or timeout) the job compares them with fresh, uncached provider rates; any difference fails
  the generation with `price_changed` and sends nothing.
- No code fails a generation the provider has accepted; only a provider update makes it final.
  Polling takes `queued` and `processing` generations only, capped per run, least recently updated or
  polled first.

## Tests (ADR 0015, ADR 0021, `tdd` skill)

- Every test is a Pest test, run against PostgreSQL.
- Tests never reach the real Pixelforge: the provider is faked at its boundary.
- Tables of cases (provider errors, pricing combinations) use Pest datasets.
- Architecture tests in `tests/Arch/` enforce the rules of "Layers" and "Boundaries" that `arch()` can
  express.
- Expected values come from the spec, a worked example or a hand-computed literal, never from
  repeating the code's calculation.

## Naming

- Domain words are the terms in [`CONTEXT.md`](CONTEXT.md), exactly: in classes, methods, columns,
  tests and UI text.
- Enum cases are TitleCase (`GenerationStatus::Succeeded`).
- Frontend code calls routes through Wayfinder's generated functions, never hard-coded URLs
  (ADR 0002).

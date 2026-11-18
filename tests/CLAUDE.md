# Tests

How to write tests is the `tdd` skill; how to run them is in
[`CODING_STANDARDS.md`](../CODING_STANDARDS.md#checks). This file holds what is specific to this
suite.

## Layout

- `Feature/<Area>/`: tests through an HTTP route, a job or an action with the database. Most tests
  live here. Every file in `Feature/` gets `RefreshDatabase` from `Pest.php`.
- `Unit/<Area>/`: tests of a class with no database or framework (for example `Money`).
- `Concurrency/<Area>/`: tests that fork several processes against committed data, using
  `DatabaseTruncation` and the `pcntl` and `posix` extensions (ADR 0039).
- `Arch/`: Pest `arch()` tests for the "Layers" and "Boundaries" rules of `CODING_STANDARDS.md`.
- The starter kit's `Feature/Auth/` and `Feature/Settings/` tests stay as they are, and keep passing.

## Rules

- The database is PostgreSQL (`phpunit.xml`, ADR 0003); a test that proves a lock or a unique
  constraint relies on it.
- The real Pixelforge is never called. Code above the boundary is tested with a fake `ImageProvider`
  bound in the container; the Pixelforge implementation is tested with Laravel's HTTP fake and
  responses copied from `docs/spec/pixelforge-api.md`.
- Webhook tests send a body signed exactly as Pixelforge signs it, with a test secret, and also cover
  a wrong signature, an old timestamp, a duplicate event and an out-of-order event.
- Money assertions compare exact nanodollar integers or exact strings; expected values come from the
  spec's worked example or a value computed by hand.
- Tables of cases use Pest datasets: one row per provider error code, one per pricing combination.
- Create test data with model factories and their states.

# 0015. Tests use Pest with architecture tests, and one command runs every quality check

- Status: accepted
- Date: 2026-11-17

## Context

The grading rewards correct money, robustness against every Pixelforge failure, and code that is easy
to read and change (`docs/spec/assessment.md#what-we-look-at`). Pixelforge documents a table of error
codes and a pricing matrix (`docs/spec/pixelforge-api.md#errors`,
`docs/spec/pixelforge-api.md#how-tokens-are-counted`). ADRs 0013 and 0014 set structural rules that
are easy to break without noticing.

## Decision

Tests are written with Pest. Datasets cover tables of cases (provider errors, pricing combinations),
and architecture tests (`arch()`) enforce the structural rules of ADRs 0013 and 0014. Code quality is
checked by Pint (formatting), Larastan at level 6 (static analysis), ESLint, Prettier and `vue-tsc`
for the frontend, and the Pest suite. One `composer check` script runs them all. A Claude Code hook
runs Pint on every PHP file Claude edits.

## Rules it creates

- Every test is a Pest test.
- Architecture tests enforce, at least: Laravel's HTTP client is used only inside provider
  implementations; controllers and jobs contain no business logic, as far as `arch()` can check it.
- Larastan runs at level 6 with no baseline of ignored errors.
- `composer check` runs Pint (in test mode), Larastan, ESLint, Prettier (check mode), `vue-tsc` and
  Pest, and fails if any of them fails.
- A ticket is not done while `composer check` fails.

## Consequences

The structural rules are enforced on every run, not only written down, and the error and pricing
tables are covered in one readable place each. Larastan is a new dev dependency, and Pest adds a thin
layer of its own syntax on top of PHPUnit.

## Alternatives considered

- **PHPUnit only**: no built-in architecture tests or datasets as compact; more ceremony per test.
- **No static analysis**: fewer tools, but type errors would only show up at runtime.
- **A stricter Larastan level**: more friction with Laravel's dynamic features for little gain here.

## In plain words

Tests are in Pest, which lets the test suite itself enforce our architecture rules and cover every
Pixelforge error in one table. One command runs formatting, static analysis, the frontend checks and
the tests, and nothing is done until it passes.

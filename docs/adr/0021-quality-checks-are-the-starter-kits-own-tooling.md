# 0021. Quality checks are the starter kit's own tooling, run by `composer check`

- Status: accepted
- Date: 2026-11-17
- Supersedes: ADR 0015

## Context

ADR 0015 chose Pest with architecture tests, Pint, Larastan at level 6, ESLint, Prettier and
`vue-tsc`, run by a new `composer check` script. It was written before the starter kit was installed.
The Laravel 13 Vue starter kit (ADR 0002) turned out to ship Larastan already, at level 7, and to
check the frontend with Vite+ (`vp check`: the Oxlint linter and a built-in formatter) instead of
ESLint and Prettier. It also ships `composer test` (Pint, Larastan, Pest) and `composer ci:check`
(the frontend check, `vue-tsc`, then `composer test`). All of it passed on the untouched kit.

## Decision

Everything in ADR 0015 stands, with these changes: Larastan runs at **level 7**, as the kit ships it.
The frontend is checked by **Vite+ (`vp check`)** and `vue-tsc`, as the kit ships them; ESLint and
Prettier are not added. `composer check` is an alias of the kit's `composer ci:check`, so it runs
`vp check`, `vue-tsc`, Pint in test mode, Larastan and Pest. Hand-written docs and agent files
(`docs/`, `.claude/`, `.junie/`, root `*.md`) are excluded from the Vite+ formatter.

## Rules it creates

- All rules of ADR 0015, except that Larastan runs at level 7 and the frontend check is
  `vp check` plus `vue-tsc`.
- `composer check` runs `composer ci:check` and fails if any check fails.
- A ticket is not done while `composer check` fails.
- The Vite+ formatter does not format `docs/`, `.claude/` or root Markdown files.

## Consequences

No tooling of our own to maintain: we follow the kit and its future updates. Level 7 is stricter than
the level 6 we planned, and it already passes on the kit with no baseline. Vite+ is newer than
ESLint and Prettier and has fewer plugins, which this app does not need.

## Alternatives considered

- **Keep ADR 0015 as written (level 6, ESLint, Prettier)**: replaces working tooling the kit
  provides with a guess made before seeing it.
- **Rename the kit's scripts**: breaks the kit's GitHub workflow and future updates for no gain; an
  alias keeps both names working.

## In plain words

I used the quality tooling the Laravel starter kit already ships: Larastan at level 7, Vite+ for the
frontend, Pint and Pest. `composer check` runs all of it, and nothing is done until it passes.

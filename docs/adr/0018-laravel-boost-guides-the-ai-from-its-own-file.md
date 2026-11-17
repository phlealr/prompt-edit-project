# 0018. Laravel Boost is a dev dependency, and its guidelines live in their own file

- Status: accepted
- Date: 2026-11-17

## Context

The user wants the AI to follow current Laravel 13 practices. Laravel Boost is Laravel's official
package for that: version-aware guidelines for Laravel, Inertia, Pest, Pint and Wayfinder, plus an MCP
server that can read the app's routes, configuration, database schema and logs and search the docs
for the installed versions. Its installer writes a guidelines block into `CLAUDE.md`, which would make
the root router long and always loaded (`docs/agents/writing-for-agents.md`).

## Decision

Laravel Boost is installed as a dev dependency with its MCP server enabled. Its guidelines are kept in
`docs/agents/laravel.md`, which the root `CLAUDE.md` points to with one line, instead of being written
into `CLAUDE.md`. The generated guidelines are reviewed with the user, and anything that contradicts
an ADR is removed.

## Rules it creates

- Laravel Boost is in `require-dev` only.
- The root `CLAUDE.md` contains no Boost guidelines block, only a pointer to `docs/agents/laravel.md`.
- `docs/agents/laravel.md` contains nothing that contradicts an ADR.

## Consequences

The AI works from guidelines that match the installed versions, and can inspect the running app. The
guidelines file must be re-checked when Boost regenerates it, so it does not drift from our ADRs.

## Alternatives considered

- **No Boost**: no new dependency, but practices depend on remembering to look up the docs.
- **Boost with its default `CLAUDE.md` block**: a large always-loaded block that would bury the
  router.

## In plain words

Boost gives the AI Laravel's own up-to-date guidelines and a view into the running app. I kept its
guidelines in a separate file and removed anything that conflicted with our decisions.

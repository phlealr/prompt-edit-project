# PromptEdit take-home: image generation on Pixelforge

A Laravel + Vue + Inertia app where signed-in users generate images through the Pixelforge API and
are charged Pixelforge's cost plus a 25% markup. Fixed by the brief, not open to change: the stack,
the markup, and the grading order (correct money and robustness before polish).

## Current phase

**Setting up the AI workflow. There is no application code yet.** Follow the order below; finish a
step before starting the next, and change no code before step 5.

1. Skeleton: specs, agent-writing guide, this file. **Done.**
2. Process skills in `.claude/skills/`. **Done.**
3. Decisions, one ADR each, reached with the user. **Done.**
4. Coding standards, glossary and folder `CLAUDE.md` files, written from those ADRs. **Next.**
5. Spec, then small tickets, then implementation one ticket at a time.

## Start here

| Read | When |
| --- | --- |
| [`docs/spec/assessment.md`](docs/spec/assessment.md) | **Scoping** anything: what is required, what is a stretch goal, how it is graded. |
| [`docs/spec/pixelforge-api.md`](docs/spec/pixelforge-api.md) | **Touching** Pixelforge: requests, errors, limits, pricing, webhooks, test keywords. |
| [`docs/agents/writing-for-agents.md`](docs/agents/writing-for-agents.md) | **Writing** or editing a `CLAUDE.md`, skill, agent or `docs/agents/` file. |
| [`docs/agents/tickets.md`](docs/agents/tickets.md) | **Creating**, picking or closing a ticket in `tickets/`. |
| [`docs/agents/laravel.md`](docs/agents/laravel.md) | **Writing** Laravel, Inertia, Vue, Pest or Wayfinder code: the framework's own guidelines for the installed versions. |
| [`docs/adr/`](docs/adr/) | **Questioning** or changing a recorded decision. |
| [`CONTEXT.md`](CONTEXT.md) | **Naming** anything in the domain. |
| `CODING_STANDARDS.md` | **Writing** or **reviewing** code. _(Not written yet: step 4.)_ |

## Guardrails

Hard stops in every session.

- **Decisions belong to the user.** When a rule or design choice is not in an ADR, propose it with a
  recommendation and wait for an answer. Never settle it silently in code or docs.
- **Every line must be explainable.** The author walks through and extends this code live in an
  interview. Choose the plainest construct that does the job, and leave nothing in the diff the user
  has not seen and agreed to.
- **Commits and PRs describe the change only.** The user is the sole author: no co-author trailer,
  no AI, tool or vendor mention in messages, PR descriptions or code comments.
- **The spec copies are the source for Pixelforge behaviour.** Read `docs/spec/` rather than relying
  on memory. Rates come from `GET /v1/pricing` at runtime, never from a constant.

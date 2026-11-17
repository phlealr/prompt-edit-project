---
name: implement-ticket
description: Build one ticket end to end, test-first, and close it only after the user approves the diff.
argument-hint: "[path to tickets/<feature>/NN-*.md] (default: the next ticket)"
disable-model-invocation: true
---

# Implement ticket

Build exactly one ticket, with the user in the loop at every decision and at the end. The user must
be able to explain every line of the result in a live interview, so the diff stays as small and
plain as the ticket allows, and nothing outside the ticket goes into it.

Ticket format, statuses and how to pick the next one: [`docs/agents/tickets.md`](../../../docs/agents/tickets.md).

## 1. Pick the ticket

Use the ticket passed as the argument, or else the next ticket by the rule in `docs/agents/tickets.md`.
If its blockers are not all `done`, stop and tell the user which ones are missing. Set its status to
`doing`.

Done when exactly one ticket is `doing` and it is this one.

## 2. Load the context

Read the ticket, its spec, the ADRs the spec links, `CODING_STANDARDS.md`, `CONTEXT.md`, and the
`CLAUDE.md` of every folder you expect to touch.

Done when you can say which folders the ticket touches and which rules apply in each.

## 3. Settle open decisions

List every choice the ticket needs that no ADR or standard covers (a name, a status, a message, a
new class's place, a library). Run the `interview` skill on the list and `record-decision` on the
answers. A new dependency is always such a choice.

Done when the list is empty.

## 4. Build it

Build the ticket with the `tdd` skill, starting from its acceptance criteria. Change only what the
ticket needs; anything else you notice goes in the ticket's `Notes` as a follow-up.

Done when every acceptance criterion is proven by a test that went red then green, or, for a
criterion only the deployed app can show, by a manual check written in `Notes`.

## 5. Run the checks

Run every check `CODING_STANDARDS.md` lists, exactly as it lists them.

Done when all of them pass. A failure is fixed, never skipped or silenced.

## 6. Review

Run the `review-changes` skill on the changes. Fix every `must-fix` finding in scope; for each
`should-fix`, fix it or say in step 7 why not. Note out-of-scope findings in the ticket's `Notes`.
Rerun step 5 after any fix, and rerun the review after any `must-fix` fix.

Done when the review reports no `must-fix` finding in scope and the checks pass.

## 7. Walk the user through it

Present:

- each acceptance criterion and the test (or manual check) that proves it;
- the changed files, each with one line on what it does and why;
- every decision taken during the ticket, with its ADR;
- anything left in `Notes` for later.

Then ask for approval. Changes the user asks for go back to step 4.

Done when the user approves the diff.

## 8. Close

Tick the acceptance criteria, set the status to `done`, and commit the code and the ticket file
together on the current branch. The message describes the change only (guardrail in `CLAUDE.md`).
Push when the user says so.

Done when the commit exists and the ticket says `done`. Report the next ticket that can start.

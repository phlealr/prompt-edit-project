---
name: write-spec
description: Write a feature spec in docs/features/ from the brief, the ADRs and the conversation, ready to be broken into tickets.
argument-hint: <feature>
disable-model-invocation: true
---

# Write spec

A feature spec says **what** the app does for one feature, from the user's side and from Pixelforge's
side, precisely enough that tickets and tests can be cut from it. It records no new decisions: those
come from ADRs. `docs/spec/` holds the external sources (the brief, the Pixelforge reference);
`docs/features/` holds our specs.

## 1. Gather the sources

Read:

- the sections of `docs/spec/assessment.md` and `docs/spec/pixelforge-api.md` the feature touches;
- every ADR in `docs/adr/` the feature depends on;
- `CONTEXT.md`, for the terms;
- what the user said about the feature in this conversation.

Done when you can list every brief requirement and every Pixelforge behaviour (endpoint, error code,
limit, webhook case, test keyword) that bears on this feature.

## 2. Find what is still open

Anything the spec needs that the sources do not settle (a behaviour, a message, a limit, a state) is
an open question. Collect them all, then run the `interview` skill on them and `record-decision` on
the answers before going on.

Done when no open question remains.

## 3. Write the spec

File: `docs/features/<feature-in-kebab-case>.md`.

```markdown
# <Feature>

- Status: draft
- Sources: <links to the brief sections, Pixelforge sections and ADRs used>

## Goal

<What the user can do when this is done, and which brief requirement it meets.>

## Behaviour

<What the user sees and does: screens, fields, states, messages. Name every state the user can see,
and what each error looks like to them.>

## Flows

<One short numbered flow for the normal path, then one for each failure case that applies:
Pixelforge errors and limits, failed generations, duplicate or out-of-order webhooks, lost
webhooks. Say what the system does and what the user ends up seeing.>

## Acceptance criteria

- [ ] <A checkable statement, phrased so a test can prove it.>

## Out of scope

- <What this feature deliberately does not do, and where it is handled instead, if anywhere.>
```

- Use the terms in `CONTEXT.md` exactly.
- Point to ADRs for the reasons behind choices; do not restate them.
- Each acceptance criterion is checkable by a test or by a step on the deployed app, including the
  Pixelforge test keyword that triggers it (`[fail]`, `[duplicate]`, …) when there is one.

Done when the template is filled and the check in step 4 passes.

## 4. Check coverage

Go through the list from step 1, item by item:

- every brief requirement in scope maps to at least one acceptance criterion;
- every Pixelforge behaviour from step 1 is either handled in Flows and checked by a criterion, or
  listed under Out of scope with a reason.

Done when every item on the list is accounted for. Report any that are not and fix the spec.

## 5. Hand over

Show the user the spec path, the list of acceptance criteria and the out-of-scope list. When the user
approves, change `Status: draft` to `Status: approved`. The next step is `/create-tickets`.

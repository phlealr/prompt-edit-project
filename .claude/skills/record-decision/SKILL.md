---
name: record-decision
description: Write a decision the user has made as an ADR in docs/adr/, and add any domain term it fixes to CONTEXT.md. Use after an interview settles decisions, when the user states a decision directly, or when a recorded decision is changed or replaced.
argument-hint: <decision to record>
---

# Record decision

An ADR is the single source of truth for one decision and its reason. Step 4 of the project
(coding standards, glossary, folder `CLAUDE.md` files) is written from ADRs, and the user explains
these decisions in a live interview, so every ADR must be accurate, short and in plain language.

Record only what the user decided. A choice you inferred and the user never confirmed goes back to
the user (the `interview` skill), not into an ADR.

## 1. Check what exists

Read the titles in `docs/adr/` (create the folder if it does not exist).

- Same decision already recorded and unchanged: stop and tell the user.
- An existing ADR is being changed: this is a replacement (see step 4).
- Otherwise: a new ADR.

Done when you know which of the three cases this is.

## 2. Write the ADR

File: `docs/adr/NNNN-<decision-as-kebab-case>.md`. `NNNN` is the next free number, four digits
(`0001`). The slug states the decision, not the topic: `0004-tests-use-sqlite-in-memory.md`, not
`0004-tests.md`. (This is only an example of the naming; it is not a decision.)

```markdown
# NNNN. <The decision, as a sentence>

- Status: accepted
- Date: YYYY-MM-DD

## Context

<The problem and the forces behind it, in 2 to 6 sentences. Cite the spec line that creates the
problem: `docs/spec/pixelforge-api.md#<section>` or `docs/spec/assessment.md#<section>`.>

## Decision

<What we do, stated as fact, in the present tense.>

## Rules it creates

- <Each concrete, checkable rule the code must follow because of this decision.>

## Consequences

<What becomes easier, what becomes harder, what we accept as a cost.>

## Alternatives considered

- **<Option>**: <why it was not chosen>.

## In plain words

<Three sentences or fewer the user can say in the interview: what we chose and why.>
```

- Use the user's own reasons where they gave them.
- Every rule under "Rules it creates" must be checkable in code or in a test. If the decision creates
  no rule, write "None."
- Use the terms in `CONTEXT.md` exactly.

Done when every section is filled and every rule is checkable.

## 3. Update the glossary

If the decision names a domain concept, or picks one name over others, add it to `CONTEXT.md` at the
repo root (create it if missing), under a heading for its area:

```markdown
- **<Term>**: <one-sentence definition>. _Avoid_: <rejected synonyms>. _Source_: ADR NNNN.
```

Done when every domain term the ADR introduces or fixes is in `CONTEXT.md`, or the ADR introduces
none.

## 4. Replacing a decision

Accepted ADRs are not edited; they are replaced.

- Write the new ADR as in step 2, and add `- Supersedes: ADR NNNN` under its status.
- In the old ADR, change only the status line: `- Status: superseded by ADR MMMM`.
- Update every doc that states a rule from the old ADR (`CODING_STANDARDS.md`, folder `CLAUDE.md`
  files, `CONTEXT.md`) so it matches the new one.

Fixing a typo or a broken link in an accepted ADR is not a replacement; edit it in place.

Done when no doc still states a rule from the superseded ADR.

## 5. Report

Tell the user the file path, the decision title, the rules it creates, and any glossary terms added.
When this ADR is the first one, or creates `CONTEXT.md`, remove the matching "_(None yet …)_" or
"_(Not written yet …)_" note from that row of the root `CLAUDE.md` table, and turn the path into a
link.

# Tickets

Shared rules for the local tickets under `tickets/`. Created by `/create-tickets`, worked by
`/implement-ticket`.

## Where they live

`tickets/<feature>/NN-<slug>.md`: one file per ticket, numbered in dependency order. `<feature>` is
the file name of the spec in `docs/features/` the ticket comes from.

## Format

```markdown
# NN. <Title>

- Status: todo
- Blocked by: <NN, NN> | none
- Spec: docs/features/<feature>.md

## What to build

<The behaviour this ticket makes work, from the user's or the system's side. Not a file-by-file plan.>

## Acceptance criteria

- [ ] <criterion>

## Notes

<Added while working: decisions taken (with their ADR), anything left for a later ticket.>
```

No file paths or code in the ticket body: the implementation decides them, and they go stale.

## Status

| Status | Meaning | Set by |
| --- | --- | --- |
| `todo` | Not started. | `/create-tickets` |
| `doing` | Being built. Only one ticket is `doing` at a time. | `/implement-ticket`, when it starts |
| `done` | Every acceptance criterion is checked **and** the user approved the diff. | `/implement-ticket`, after approval |

## The next ticket

A ticket can start when every ticket in its `Blocked by` is `done`. A ticket already `doing` comes
before any new one. Otherwise, within a feature, the next ticket is the lowest-numbered one that can
start; when more than one feature has a ticket that can start, ask the user which feature goes next.

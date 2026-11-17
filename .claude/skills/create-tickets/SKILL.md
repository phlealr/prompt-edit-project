---
name: create-tickets
description: Break an approved feature spec into small tracer-bullet tickets in tickets/<feature>/, each with its blockers and acceptance criteria.
argument-hint: <path to docs/features/*.md>
disable-model-invocation: true
---

# Create tickets

Turn one approved spec into tickets the user can implement and review one at a time. A ticket is a
**tracer bullet**: a thin path through every layer it needs (database, backend, UI, tests) that works
end to end when done, not one layer of the whole feature.

## 1. Read the spec

Read the spec passed as the argument and the ADRs it links. Its status must be `approved`; if it is
`draft`, stop and send the user back to `/write-spec`.

Done when you have the spec's full list of acceptance criteria in front of you.

## 2. Draft the slices

Cut the feature into tickets. Each ticket:

- delivers behaviour that can be shown working on its own (in a test, or in the browser);
- crosses every layer that behaviour needs, and no more;
- is small enough for one fresh session to build, and for the user to read and explain the whole diff
  in one sitting;
- lists the tickets that must be done before it can start (**blocked by**).

Put setup that every later ticket needs (scaffolding, configuration, a shared module) first, as its
own ticket, so the later tickets stay small.

Assign every acceptance criterion from the spec to exactly one ticket. A ticket may add criteria of
its own (for example, the error case of the step it builds).

Done when every spec criterion belongs to exactly one ticket and every ticket can be shown working on
its own.

## 3. Review the breakdown with the user

Show the tickets as a numbered list, in dependency order:

```
01. <Title>: <what works when it is done>. Blocked by: none.
02. <Title>: <what works when it is done>. Blocked by: 01.
```

Ask whether the size is right, whether the blockers are right, and whether any ticket should be
split or merged. Revise until the user approves.

Done when the user approves the list.

## 4. Write the ticket files

Write one file per approved ticket, in the location and format in
[`docs/agents/tickets.md`](../../../docs/agents/tickets.md), numbered in the approved order, with
`Status: todo` and an empty `Notes` section. Use the terms in `CONTEXT.md` exactly.

Done when every approved ticket has its file.

## 5. Report

Give the user the folder path and the first ticket that can start (the rule is in
`docs/agents/tickets.md`).

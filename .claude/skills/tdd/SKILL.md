---
name: tdd
description: Build behaviour test-first in red-green cycles at seams agreed with the user. Use when implementing a ticket, fixing a bug, or adding any behaviour that a test can prove.
---

# Test-driven development

Build in small **red → green** cycles: one failing test, then the least code that makes it pass,
then the next test. The tests are the proof that the money is right and that the app survives what
Pixelforge does, so they must test behaviour, and their expected values must come from somewhere
other than the code.

How to run the tests and which tools to use: `CODING_STANDARDS.md`. Use the terms in `CONTEXT.md`
in test names.

## 1. Agree the seams

A **seam** is the public boundary a test drives and observes: an HTTP endpoint, a job, a class's
public method. Tests go at seams, never against internals.

Before the first test, propose to the user:

- the seams you will test, each with one line on what it catches and what it misses;
- what each test fakes, if anything, and why.

Prefer few seams, as high as the behaviour allows: a test through the webhook endpoint proves more
than one through a helper it calls.

Done when the user has agreed the seams and the fakes.

## 2. List the behaviours

Write the behaviours to prove as a short list, in the order you will build them: the normal case
first, then each failure or edge case from the ticket's acceptance criteria.

Done when every acceptance criterion of the ticket maps to at least one behaviour on the list.

## 3. Cycle: red, then green

For each behaviour on the list, in order:

1. **Red.** Write one test for this behaviour only. Run it and see it fail **for the reason you
   expect** (the behaviour is missing, not a typo or a missing import).
2. **Green.** Write the least code that makes it pass. Run the test, then the tests around it.
3. Tick the behaviour off the list.

Done when every behaviour on the list has a test that went red and then green, and the whole suite
passes.

## What makes a test worth keeping

- **It tests behaviour.** It drives a seam and checks what a caller or user would see: the response,
  the stored result, the state reached. It survives a rewrite of the internals.
- **Its expected value is independent.** It comes from the spec (the Pixelforge worked example, a
  documented error case), a value worked out by hand, or a literal; never from repeating the
  calculation the code does. A test that computes its expected value the way the code does passes by
  construction and proves nothing.
- **Its name states the behaviour**: what happens, under what condition.

## Traps

- **Testing internals.** Mocking the class's own collaborators, calling private methods, or checking
  the database when the seam itself shows the result. The sign: the test breaks on a refactor that
  changed no behaviour.
- **All tests first.** Writing every test before any code tests imagined behaviour. Write one test,
  make it pass, and let what you learned shape the next.
- **Speculative code.** Code no failing test asked for. Add it when a test needs it.
- **Refactoring inside the cycle.** Keep the cycle to red and green. Clean-up happens in review, on
  green tests.

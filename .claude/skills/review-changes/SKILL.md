---
name: review-changes
description: Review a diff with three independent reviewers (spec, correctness of money and Pixelforge handling, standards) and report verified findings. Use before closing a ticket, when the user asks for a review, or after changes to money or Pixelforge code.
argument-hint: "[git ref to compare against] (default: uncommitted changes)"
---

# Review changes

Three reviewers read the same diff, each from one angle and each in its own subagent, so none is
biased by the reasoning that wrote the code or by the other reviewers. You collect, verify and
report. You change no code in this skill.

## 1. Fix the diff

The diff is the uncommitted changes (`git diff HEAD` plus untracked files), or, when a ref is given,
`git diff <ref>...HEAD`. Find the ticket it belongs to: the ticket with `Status: doing`, or one the
user names.

Done when the diff is non-empty and you know its ticket, or the user has said there is none.

## 2. Run the reviewers in parallel

Start the three reviewers below as subagents in one message. Give each:

- the diff command from step 1 and the list of changed files;
- its own sources and question, exactly as below;
- the report format below.

Give them no summary of the change and no opinion of your own: they read the code themselves.

**Spec reviewer.** Sources: the ticket, its spec in `docs/features/`, the ADRs the spec links.
Question: does the diff do what the ticket asks, all of it and nothing else? Check every acceptance
criterion against the code and its tests, and flag anything built that no criterion asked for.

**Correctness reviewer.** Sources: `docs/spec/pixelforge-api.md`, `docs/spec/assessment.md`
("What we look at"), the ADRs on money and Pixelforge. Question: can this diff charge a wrong amount,
charge twice, charge for a failed generation, or end in a wrong state when Pixelforge rate-limits,
overloads, fails a generation, or delivers webhooks twice, out of order, early or never? For every
path that touches money or generation state, follow it through and name the input that breaks it.

**Standards reviewer.** Sources: `CODING_STANDARDS.md`, `CONTEXT.md`, the `CLAUDE.md` of every
folder the diff touches. Question: does the code follow them, use the glossary terms, and read
plainly enough that its author can explain every line in a live walkthrough? Name what is unclear
and what a plainer version would be.

Report format, for every reviewer:

```
- [must-fix | should-fix | note] <file>:<line>: <what is wrong>.
  Why: <the rule, criterion or failure scenario it breaks>.
  Fix: <the change, in one line>.
```

`must-fix`: wrong behaviour, wrong money, a missed acceptance criterion, or a broken documented rule.
`should-fix`: works but is harder to read or change than it needs to be. `note`: worth knowing, no
change needed. If nothing is found: `No findings.`

Done when all three reviewers have reported.

## 3. Verify every finding

For each finding, read the code it points to and confirm it is real: the line exists, the scenario
can happen, the rule says what the reviewer claims. Drop findings that fail, and merge duplicates
across reviewers.

Done when every remaining finding has been checked against the code.

## 4. Report

Report the verified findings grouped by severity, `must-fix` first, each tagged with its reviewer.
Then list, in one line each, the findings you dropped and why. Stop here: fixing is up to the caller
(`/implement-ticket`) or the user.

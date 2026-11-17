---
name: interview
description: Reach decisions with the user by questioning a plan, design or open choice in rounds until nothing is left assumed. Use when the user wants to decide or stress-test something, when a task hits a design choice no ADR covers, or before writing a spec whose decisions are still open.
argument-hint: <topic to decide>
---

# Interview

Turn an open topic into settled decisions the user owns. You find the facts; the user makes the
choices. The output is a list of decisions ready for `record-decision`, nothing else: no code, no
docs edits, no ADRs during the interview.

## 1. Load what is already settled

Read, before the first question:

- the parts of `docs/spec/` the topic touches (the brief and the Pixelforge reference);
- every ADR in `docs/adr/` on the topic;
- `CONTEXT.md`, if it exists, so questions use the domain terms.

Done when you can name, for the topic, what the spec fixes, what ADRs already decided, and what is
still open.

## 2. Map the decision tree

List the open decisions and what each depends on. A decision is on the **frontier** when everything
it depends on is already settled: it can be asked now without guessing an earlier answer.

Facts are your job. When a decision needs a fact (what Pixelforge does, what Laravel offers, what the
code already has), look it up in the spec, the docs or the code, or with a subagent, and never ask
the user for it. A pending lookup blocks only the questions that depend on it.

Done when every open decision is on the tree with its dependencies.

## 3. Ask in rounds

Each round asks **at most four** frontier questions, the ones others depend on most first. Write them
in chat, in this format, and wait for the answers:

```
**Q1. <short title>**
<the question: the options, and what each one costs or risks>
Spec: <the line in docs/spec/ that makes this a question, or "not in the spec">

➡️ Recommended: <your answer and the one-line reason>
```

- Word each question so that "yes" accepts the recommendation.
- Give a real recommendation every time, grounded in the spec and the grading criteria (correct
  money and robustness before polish).
- When an option would contradict the spec or an ADR, say so in the question.
- Use the chat format above, never a pop-up question form.

After each round, update the tree: answers settle decisions, which move new ones onto the frontier.
An answer that raises a new question adds it to the tree.

Done when the frontier is empty: every decision on the tree is answered and none is left assumed.

## 4. Confirm the result

Present the settled decisions as a numbered list, one line each: the decision and its reason, in the
user's words where they gave them. Ask the user to confirm it is complete and correct.

Done when the user confirms. Then hand each decision to the `record-decision` skill.

# Writing for agents

How to write any document an agent reads in this repo: a `CLAUDE.md`, a skill, an agent definition,
or a doc reached through a pointer. Read it before creating or editing one. For the parts specific to
skills and agents (frontmatter, invocation), also read [`skill-mechanics.md`](skill-mechanics.md).

The goal is a **predictable process**: the agent should take the same path through a document on
every run, even though its output differs from task to task.

## Pointers decide when material is read

A **pointer** is a line that sits in the agent's context and names material outside it: a row in the
`CLAUDE.md` routing table, a skill's `description`, a "see X" line inside a doc. The agent reaches the
target only when the pointer's wording matches what it is doing, so the wording matters more than the
target.

- **Lead with the trigger.** Start with the situation that should make the agent open the target
  ("**Placing** a class: …", "**Changing** money code: …").
- **One trigger per distinct case.** List each situation the target handles; drop synonyms that name
  the same situation twice.
- **Say nothing the target already says.** The pointer routes; the target explains.

When a must-read document is being skipped, sharpen its pointer first. Copy the material inline only
if a sharper pointer still fails.

## Two costs

Every line spends one of two budgets:

- **Context cost.** Always-loaded text (`CLAUDE.md`, skill descriptions) is paid on every turn,
  whether or not it is relevant. Keep it to routing and hard rules.
- **Human cost.** Material with no pointer at all depends on a person remembering it exists. Spend
  this only where human judgement belongs (a skill only the user should start, for example).

Material behind a pointer costs only the pointer line until it is needed. That is the default home for
anything not every task needs.

## Steps and reference

A document holds two kinds of content:

- **Steps**: ordered actions the agent performs.
- **Reference**: rules, definitions and facts looked up when needed.

Rank them by how soon the agent needs them:

1. Steps, in the file, in order.
2. Reference every path needs, in the file, beside the step that uses it.
3. Reference only some paths need, in a separate file behind a pointer.

The test for moving something out: **does every path through the document need it?** If not, it goes
behind a pointer. Reference left in the middle of steps buries them and makes the agent skip steps at
random.

Keep each concept's definition, rules and caveats under one heading, so reading one part brings the
rest.

A document that is long even though every line is live is still a problem: attention spreads thin.
Split it by case or by sequence.

## Every step ends on a completion criterion

A step needs a condition that tells the agent it is done.

- **Checkable.** "Every Pixelforge error code in the table has a test" is checkable; "error handling
  is understood" is not. A vague criterion invites the agent to stop early, pulled by the steps it
  can see ahead.
- **Demanding enough.** The criterion sets how much digging happens. "Every place that writes a
  charge has been read" forces a thorough search; "list the charge code" does not.

If the agent still rushes a step after its criterion is sharp, split the remaining steps into a
separate skill or a subagent, so they are out of sight while the current step runs.

## Use words the model already knows

A short, well-known term carries a whole behaviour with it: _idempotent_, _tracer bullet_, _red_
(a test that fails on the bug), _source of truth_. Use the same term every time, never a paraphrase,
so it builds one meaning across the documents, the code and your prompts.

Prefer an existing term to an invented one; an invented one has to be defined, and it brings no prior
behaviour with it. Collapse any phrase that keeps spelling out an idea into its term.

Domain terms come from [`CONTEXT.md`](../../CONTEXT.md) once it exists. Use them exactly.

## State the behaviour you want

Write the target behaviour ("write one-line commit subjects"), not the forbidden one alone. A
prohibition puts the unwanted idea in context and can make it more likely. When a hard rule can only
be written as a "never", pair it with what to do instead.

## Keep it short and current

- **One source of truth per meaning.** A rule lives in one place; everything else points to it.
  Changing behaviour should be a one-file edit.
- **Do not copy what the environment already says.** `composer.json` scripts, config files, the
  folder layout and `--help` output are sources of truth. Write down only what cannot be found by
  looking: the reason behind a choice, an unwritten convention, a trap.
- **Delete no-ops.** A sentence that tells the model to do what it already does by default changes
  nothing. Remove the whole sentence. If a default is not strong enough, use a stronger word rather
  than more words.
- **Prune when behaviour changes.** A stale line is worse than a missing one. When a decision changes,
  update its single source and remove what no longer applies.

# Skill and agent mechanics

The parts of [`writing-for-agents.md`](writing-for-agents.md) specific to skills
(`.claude/skills/`) and agents (`.claude/agents/`). Everything about how to write the body is in that
file.

## Skill layout

```
.claude/skills/<name>/
├── SKILL.md          ← frontmatter + steps; the part every run reads
└── <TOPIC>.md        ← reference only some runs need, pointed to from SKILL.md
```

- `<name>` is kebab-case, plain words, no project prefix. It must not collide with a built-in or
  globally installed skill (`code-review` and `simplify` already exist).
- Frontmatter: `name`, `description`, and optionally `argument-hint` (shown when the user types the
  skill) and `disable-model-invocation`.

## Who can start a skill

Choose this per skill:

- **The agent and the user** (default, no `disable-model-invocation`). The `description` stays in
  context on every turn, so the agent can start the skill on its own and other skills can call it.
  Write the description as a pointer: what it does, then each situation that should trigger it.
  Use this when the agent must reach the skill without being asked, or when the skill holds
  reference that several other skills share.
- **The user only** (`disable-model-invocation: true`). The description is never loaded for the
  agent; it becomes a one-line summary for people. No context cost, but someone has to remember the
  skill exists. Use this for workflows a person should deliberately start.

Reference shared by two user-only skills cannot live in either of them, since neither can start the
other. Put it in a plain file under `docs/agents/` and point to it from both.

## When to split a skill

- **By sequence:** later steps are pulling the agent to rush an earlier one. Moving them into another
  skill hides them until they are needed.
- **By trigger:** part of the skill should fire on its own trigger word, or another skill needs to
  call just that part. Splitting adds a description to the always-loaded context, so the separate
  reach has to be worth it.

## Router skill

When there are more user-only skills than a person can keep track of, add one user-only skill that
lists them and says when to use each. It can only suggest; it cannot start them.

## Agents

An agent (`.claude/agents/<name>.md`) runs in its own context and returns only a conclusion. Use one
when the work needs a fresh, unbiased context (a reviewer that has not seen the implementer's
reasoning) or would flood the main context with file reads.

- Frontmatter: `name`, `description` (when to use it, written as a pointer), `tools` (only what the
  job needs), `model`.
- The body is the agent's whole briefing: its role, its inputs, its steps with completion criteria,
  and the exact shape of the report it returns.
- An agent cannot ask the user questions. Anything needing a decision goes back in its report.

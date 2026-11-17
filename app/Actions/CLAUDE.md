# Actions

All business logic lives here (ADR 0020). Shared rules: [`CODING_STANDARDS.md`](../../CODING_STANDARDS.md).
`Fortify/` is the starter kit's and follows Fortify's contracts, not these rules.

## Shape

- One class per action in `app/Actions/<Area>/`, named verb + noun, no suffix: `SubmitGeneration`.
- One public method, `handle()`, with typed parameters and a typed return.
- Parameters are domain values (models, `Money`, enums, scalars), never a `Request`: a controller, a
  job and a command must be able to call it the same way.
- Dependencies (other actions, the `ImageProvider`) come through the constructor; Laravel's container
  builds the action.

## Behaviour

- An action that writes money or a generation's status owns its database transaction, and takes the
  row locks its rule needs (the user's row for a balance check and hold, ADR 0006).
- One action applies a provider update to a generation: it moves the status forward only, and writes
  the `release` and `charge` rows. Submission, webhook and poll all call it (ADR 0010). No other code
  changes a generation's status or writes a charge.
- Running an action twice with the same input leaves the same result: forward-only statuses and the
  ledger's unique (generation, type) rows make repeats harmless (ADR 0006, ADR 0008).
- A rule broken by the caller's input throws an exception named after the rule. Provider failures
  arrive as the exceptions of `app/ImageProviders/Exceptions/`; an action decides what each means for
  the generation (retry later, or fail with a reason).

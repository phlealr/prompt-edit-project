# HTTP

Entry points only: no business logic here (ADR 0013, ADR 0020). Shared rules:
[`CODING_STANDARDS.md`](../../CODING_STANDARDS.md).

## Controllers

- `Controllers/<Area>/<VerbNoun>Controller.php`, a single `__invoke`.
- The body: take the validated input from the Form Request, call one action, return
  `Inertia::render()` or a redirect. Nothing else.
- Middleware and authorization are attributes on the class: `#[Middleware('auth')]`,
  `#[Authorize(...)]`. The operator report is authorised through a Gate (ADR 0016).
- Props sent to pages carry money already formatted by `Money`, never a raw nanodollar integer to
  format in JavaScript (ADR 0007).

## Form Requests

- `Requests/<Area>/<VerbNoun>Request.php`: validation and authorization of the request only.
- Validation mirrors the provider's documented limits where the user can hit them (prompt length,
  sizes, qualities, at most 4 reference image URLs: `docs/spec/pixelforge-api.md#create-a-generation`),
  so the user sees the error on the form instead of a failed generation.

## Webhooks

The provider's webhook route is the one controller that is not called by our own pages
(`docs/spec/pixelforge-api.md#webhooks`, ADR 0010):

- Its route is registered outside the `web` middleware group, so CSRF does not apply.
- The controller hands the **raw** request body and headers to one action and returns its answer.
  Signature checking belongs to the provider implementation, recording the event to the action.
- It answers fast: verify, record, dispatch, `200`. Applying the update happens in a job.

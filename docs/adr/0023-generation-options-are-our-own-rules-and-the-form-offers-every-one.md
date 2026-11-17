# 0023. Generation options are our own rules, and the form offers every one

- Status: accepted
- Date: 2026-11-17

## Context

Users choose a prompt, a size, a quality and optional reference image URLs
(`docs/spec/assessment.md#what-to-build`). Pixelforge accepts a prompt of at most 4,000 characters,
three sizes, three qualities, at most 4 http(s) reference image URLs, and a `metadata` object, and
rejects anything else with `422 validation_failed`
(`docs/spec/pixelforge-api.md#create-a-generation`). ADR 0014 keeps Pixelforge's specifics inside its
implementation, and ADR 0009 already uses `metadata` to carry our generation id.

## Decision

The generation options are our own product rules. Sizes (`1024x1024`, `1536x1024`, `1024x1536`) and
qualities (`low`, `medium`, `high`) are enums in `app/Enums/`; the prompt limit (4,000 characters)
and the reference image rules (at most 4, each an http(s) URL) are checked in the Form Request. The
generate form offers every option a user can choose: the prompt, every size, every quality and 0 to 4
reference image URLs, defaulting to `1024x1024` and `medium`. `metadata` is not on the form; it only
carries our generation id.

## Rules it creates

- Sizes and qualities are defined once, as enums in `app/Enums/`, and the form lists every case.
- The Form Request requires a prompt of at most 4,000 characters, a size and a quality from the
  enums, and at most 4 reference image URLs, each `http` or `https`.
- The form's defaults are `1024x1024` and `medium`.
- No user input reaches the provider's `metadata`.

## Consequences

The rules are readable in two enums and one Form Request, and the form never offers what the
provider would reject. Swapping to a provider with different options means editing the enums and the
Form Request, besides the new implementation.

## Alternatives considered

- **The provider interface exposes its options and limits**: a provider swap would update the form
  automatically, but it adds interface surface for a second provider that does not exist (ADR 0014).
- **Let users set `metadata`**: it would let them break how webhooks are matched to generations.

## In plain words

The sizes, qualities and limits are ours, written as two enums and one validation class, and the
form shows every option Pixelforge offers. The only thing users can't touch is the metadata, because
that's how we recognise our generations in webhooks.

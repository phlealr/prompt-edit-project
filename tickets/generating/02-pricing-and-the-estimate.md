# 02. Pricing and the estimate

- Status: todo
- Blocked by: 01
- Spec: docs/features/generating.md

## What to build

The provider interface gains its first method: estimating a generation from its prompt, size,
quality and number of reference images. The Pixelforge implementation reads the rates and token
rules from the provider's pricing endpoint, caches them for a configured time, and returns the range
of charges, markup included, with the rates it used (ADR 0011, ADR 0014, ADR 0024). A fake provider
lets every later test run without the network. Shown working by tests against a faked pricing
response.

## Acceptance criteria

- [ ] For a 400-character prompt, `high`, `1536x1024` and 2 reference images at the published rates,
      the estimate's maximum is the charge for a provider cost of $0.373875000, i.e. $0.467343750.
- [ ] The estimate's minimum uses −15% text tokens (rounded down) and the quality's minimum
      reasoning tokens; its maximum uses +15% text tokens (rounded up) and the quality's maximum
      reasoning tokens.
- [ ] A duplicated reference image URL counts twice in the estimate.
- [ ] Rates come from the provider's pricing endpoint and are cached for the configured time; a
      change of rates is reflected after the cache expires.
- [ ] Text tokens count Unicode characters, not bytes.
- [ ] When the pricing endpoint cannot be reached and nothing is cached, the estimate fails with our
      own exception, not a provider error.

## Notes

# 11. Configurable markup

- Status: done
- Blocked by: none
- Spec: docs/features/generating.md

## What to build

The markup is a whole percentage from the environment, 25 by default (ADR 0045). The provider
estimates its own costs; our actions add the markup. Each generation keeps the markup of its hold,
and is charged with it.

## Acceptance criteria

- [x] `MARKUP_PERCENT` sets the markup; without it, it is 25.
- [x] A charge is ceil(cost × (100 + markup) / 100): the worked example still charges $0.467343750 at
      25%.
- [x] A generation stores the markup percent of its hold; its charge uses it even if the setting
      changed since.
- [x] Existing generations are recorded with 25.
- [x] The home page shows the configured markup.

## Notes

- Decision: each generation is charged the markup of its hold (ADR 0045, user, 2026-11-18).
- `MARKUP_PERCENT` (`config/credits.php`, default 25) must be a whole number from 0 to 1000; anything
  else stops the app at boot rather than charging a wrong markup.
- `Money::plusMarkup(int $percent)` computes ceil(cost × (100 + p) / 100); at 25 it equals the former
  ceil(cost × 5 / 4) exactly, so every existing amount is unchanged.
- The provider's `Estimate` now holds provider costs; `EstimateGeneration` and `SubmitGeneration`
  add the markup, and the generation stores `markup_percent`; `ApplyProviderUpdate` charges with it.
  Existing generations were backfilled with 25.
- The home page reads the configured markup. ADR 0005 and ADR 0011 are marked as amended, and the
  generating spec, the coding standards and the glossary were aligned.

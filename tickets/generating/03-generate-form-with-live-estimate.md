# 03. Generate form with live estimate

- Status: done
- Blocked by: 02
- Spec: docs/features/generating.md

## What to build

A signed-in user opens the generate page and fills in the form: prompt with its counter, every size
and quality with their defaults, and 0 to 4 reference image URLs added and removed as rows. The
estimate range updates as they type, debounced, from the server. When the price is unavailable the
form says so, disables Generate, and asks again every 10 seconds until a price arrives. The Generate
button does nothing yet beyond what later tickets add.

## Acceptance criteria

- [x] A guest opening the generate page is redirected to sign in.
- [x] The form lists every size and every quality, with `1024x1024` and `medium` selected.
- [x] The form accepts 0 to 4 reference image URLs, adds and removes fields, and offers no fifth.
- [x] The form has no field for the provider's metadata.
- [x] The prompt counter shows the number of characters typed out of 4,000.
- [x] Amounts on the page use 4 decimals and show the exact amount on hover.
- [x] When the pricing cannot be fetched, the page shows "Price unavailable right now" and Generate
      is disabled.
- [x] While the price is unavailable, the page asks for the estimate every 10 seconds, and shows the
      range and enables Generate as soon as one arrives, without any action from the user.
- [x] The estimate is refreshed, debounced, when the prompt, size, quality or number of reference
      images changes, and the frontend contains no pricing logic.

## Notes

- Decisions: the estimate comes from its own JSON route through `useHttp`, debounced by 400 ms, and
  the route accepts the form as it is being typed (ADR 0033).
- `Money` serializes to `{user, exact}`; `Amount.vue` shows the user format with the exact amount as
  its `title` (hover).
- Manual checks on the local app (no JS test runner), 2026-11-17:
  - counter shows 15 / 4000 for 15 Unicode characters including an emoji;
  - "Add reference image" adds up to 4 fields then disappears; Remove removes one;
  - no metadata field on the page;
  - each amount shows the exact 9-decimal value on hover;
  - 15 keystrokes sent 1 estimate request (debounce);
  - with pricing unreachable: "Price unavailable right now", Generate disabled, a new request every
    10 s; once pricing came back, the range appeared and Generate enabled with no user action;
  - a prompt over 4,000 characters shows the validation message instead of an estimate.
- Any non-422 failure of the estimate route (including an expired session) shows "Price unavailable
  right now" and retries.
- A literal URL in a template attribute breaks vue-tsc's scope tracking; the placeholder is a script
  constant for that reason.
- Follow-up: Fortify still enables email verification and two-factor, which ADR 0017 disables.

# 03. Generate form with live estimate

- Status: todo
- Blocked by: 02
- Spec: docs/features/generating.md

## What to build

A signed-in user opens the generate page and fills in the form: prompt with its counter, every size
and quality with their defaults, and 0 to 4 reference image URLs added and removed as rows. The
estimate range updates as they type, debounced, from the server. When the price is unavailable the
form says so, disables Generate, and asks again every 10 seconds until a price arrives. The Generate
button does nothing yet beyond what later tickets add.

## Acceptance criteria

- [ ] A guest opening the generate page is redirected to sign in.
- [ ] The form lists every size and every quality, with `1024x1024` and `medium` selected.
- [ ] The form accepts 0 to 4 reference image URLs, adds and removes fields, and offers no fifth.
- [ ] The form has no field for the provider's metadata.
- [ ] The prompt counter shows the number of characters typed out of 4,000.
- [ ] Amounts on the page use 4 decimals and show the exact amount on hover.
- [ ] When the pricing cannot be fetched, the page shows "Price unavailable right now" and Generate
      is disabled.
- [ ] While the price is unavailable, the page asks for the estimate every 10 seconds, and shows the
      range and enables Generate as soon as one arrives, without any action from the user.
- [ ] The estimate is refreshed, debounced, when the prompt, size, quality or number of reference
      images changes, and the frontend contains no pricing logic.

## Notes

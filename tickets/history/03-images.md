# 03. Images

- Status: todo
- Blocked by: 02
- Spec: docs/features/history.md

## What to build

A succeeded card shows its image from the provider's URL until it expires, opening it full size in a
new tab; afterwards it shows "Image expired" without requesting it, and an image that fails to load
before then shows "Image unavailable" (ADR 0012).

## Acceptance criteria

- [ ] A succeeded card before its expiry shows the image, which opens full size in a new tab.
- [ ] A succeeded card after its expiry shows "Image expired" and does not request the image.
- [ ] An image that fails to load before its expiry shows "Image unavailable".
- [ ] A card whose expiry passes while the page is open switches to "Image expired" without a
      reload.

## Notes

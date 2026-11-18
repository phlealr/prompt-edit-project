# 03. Images

- Status: done
- Blocked by: 02
- Spec: docs/features/history.md

## What to build

A succeeded card shows its image from the provider's URL until it expires, opening it full size in a
new tab; afterwards it shows "Image expired" without requesting it, and an image that fails to load
before then shows "Image unavailable" (ADR 0012).

## Acceptance criteria

- [x] A succeeded card before its expiry shows the image, which opens full size in a new tab.
- [x] A succeeded card after its expiry shows "Image expired" and does not request the image.
- [x] An image that fails to load before its expiry shows "Image unavailable".
- [x] A card whose expiry passes while the page is open switches to "Image expired" without a
      reload.

## Notes

- The card carries the provider's image URL and expiry; `GenerationImage.vue` decides between the
  image (linking full size in a new tab, `rel="noopener"`), "Image expired" (no request) and "Image
  unavailable" (`@error`). It watches the expiry prop, so a card that becomes succeeded on a later
  reload also switches when its expiry passes.
- Only an `https://` image URL from the provider is kept, since it goes straight into a link.
- Manual checks on the local app, 2026-11-18: a valid image showed and linked full size; an
  already-expired card showed "Image expired" and its URL was never requested; a broken URL showed
  "Image unavailable"; a card expiring 20 s later switched to "Image expired" without a reload.

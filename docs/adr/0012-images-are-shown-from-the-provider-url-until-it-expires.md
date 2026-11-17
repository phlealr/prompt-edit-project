# 0012. Images are shown from the provider's URL until it expires

- Status: accepted
- Date: 2026-11-17

## Context

A succeeded generation's image URL stops working 24 hours after completion, and fetching a fresh URL
does not extend that (`docs/spec/pixelforge-api.md#images`). The brief says it is fine if images stop
loading after the URLs expire (`docs/spec/assessment.md#what-to-build`).

## Decision

A generation stores the provider's image URL and its expiry time. The history shows the image until
then, and an "Image expired" state afterwards. The app does not download or store images. Storing
them is optional polish for the very end, if time allows.

## Rules it creates

- A succeeded generation stores its image URL and the URL's expiry time.
- After the expiry time, the UI shows the "Image expired" state instead of requesting the image.

## Consequences

No storage, download job or storage cost. Reviewers browsing more than a day later see expired
placeholders for older generations. Adding storage later goes through Laravel's `Storage`
(ADR 0014), so it does not change existing code paths.

## Alternatives considered

- **Download every image into our own storage**: images last forever, but it adds a job, storage
  configuration, failure handling and cost for something the brief does not require.

## In plain words

The brief accepts expiring images, so we show Pixelforge's image until its URL expires and then say
so clearly. Storing images ourselves would be easy to add later, but was not worth the time first.

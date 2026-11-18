# 0043. Server errors and unreachable pricing during submission are retried with backoff

- Status: accepted
- Date: 2026-11-18
- Amends: ADR 0022

## Context

ADR 0022 retries rate limits, concurrency limits, overloads and network errors with backoff, and
fails at once on errors waiting cannot fix. Two answers fit neither list: a provider server error
other than `503 overloaded` (a `500`, which may have created the generation), and the provider's
pricing being unreachable during the rate check before an attempt. Left to the queue, they were
retried instantly, with no wait.

## Decision

A provider server error on a create request is treated like a network error: the generation may
exist at the provider (ADR 0040) and the attempt is retried after its backoff. Pricing being
unavailable during the rate check is retried after the attempt's backoff; nothing was sent.

## Rules it creates

- A `5xx` answer to a create request, other than `503 overloaded`, sets the generation's "may exist
  at the provider" flag and is retried after the attempt's backoff.
- Pricing unavailable during the rate check is retried after the attempt's backoff.

## Consequences

Pixelforge's random server failures cost a wait, not a failed generation. Every error the job sees is
either retried with backoff or fails the generation for a recorded reason.

## Alternatives considered

- **Fail at once on any other error**: simplest, but one transient `500` fails a generation.

## In plain words

A Pixelforge server error, or its prices being briefly unreachable, is waited out like a timeout,
with the same backoff and attempt limit, instead of failing the generation.

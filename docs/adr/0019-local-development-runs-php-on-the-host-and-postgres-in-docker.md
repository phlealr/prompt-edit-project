# 0019. Local development runs PHP on the host, PostgreSQL in Docker, and a tunnel for webhooks

- Status: accepted
- Date: 2026-11-17

## Context

PostgreSQL is used everywhere (ADR 0003). Pixelforge only delivers webhooks to public URLs and rejects
`localhost`, recommending a tunnel during development
(`docs/spec/pixelforge-api.md#local-development`). The brief recommends one Pixelforge API key per
environment so each receives only its own events and usage can match exactly
(`docs/spec/assessment.md#tips`). The development machine has PHP 8.5 and Docker, and no tunnel tool
installed.

## Decision

The app runs on the host's PHP with the starter kit's `composer run dev` (web server, queue worker,
Vite and logs). A `compose.yaml` runs only PostgreSQL, with one database for development and one for
tests. Cloudflare Tunnel (`cloudflared`) exposes the app to Pixelforge's webhooks; ngrok is the
fallback if the changing quick-tunnel URL becomes a nuisance. Local and production each have their
own Pixelforge API key, webhook URL and signing secret.

## Rules it creates

- `compose.yaml` defines the PostgreSQL service only.
- Development and tests use different databases.
- Each environment has its own Pixelforge API key and webhook signing secret, in `.env`; none is
  committed.

## Consequences

Commands run directly on the host, which is simple for both the user and the AI. A quick tunnel's URL
changes on each restart, so the local key's webhook URL must be updated in the Pixelforge dashboard
each time. Polling (ADR 0010) keeps the app correct even when the tunnel is down.

## Alternatives considered

- **Laravel Sail (everything in Docker)**: full parity, but every command goes through containers.
- **ngrok as the default**: a stable free URL, but needs an account.
- **No tunnel**: webhooks could not be tested end to end locally.

## In plain words

Locally I run PHP directly and only PostgreSQL in Docker. A Cloudflare tunnel lets Pixelforge's
webhooks reach my machine, and each environment has its own API key, as Pixelforge recommends.

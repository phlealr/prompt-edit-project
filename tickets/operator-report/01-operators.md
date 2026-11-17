# 01. Operators

- Status: todo
- Blocked by: generating 04
- Spec: docs/features/operator-report.md

## What to build

An operator can be made with `php artisan user:make-admin {email}`. Operators see an Operator report
entry in the sidebar and can open the report page (empty for now); everyone else gets `403` (ADR
0016).

## Acceptance criteria

- [ ] `user:make-admin {email}` sets `is_admin` for that user; an unknown email prints an error and
      changes nothing.
- [ ] No web request can set `is_admin`.
- [ ] A non-operator gets `403` on the report, and does not see it in the sidebar; a guest is
      redirected to sign in.

## Notes

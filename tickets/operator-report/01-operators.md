# 01. Operators

- Status: done
- Blocked by: generating 04
- Spec: docs/features/operator-report.md

## What to build

An operator can be made with `php artisan user:make-admin {email}`. Operators see an Operator report
entry in the sidebar and can open the report page (empty for now); everyone else gets `403` (ADR
0016).

## Acceptance criteria

- [x] `user:make-admin {email}` sets `is_admin` for that user; an unknown email prints an error and
      changes nothing.
- [x] No web request can set `is_admin`.
- [x] A non-operator gets `403` on the report, and does not see it in the sidebar; a guest is
      redirected to sign in.

## Notes

- `php artisan user:make-admin {email}` (`app/Console/Commands/MakeAdminCommand.php`, Laravel's
  default place) calls `MakeOperator`, which force-fills `is_admin`; `is_admin` is not fillable, and a
  test sends it to registration and the profile update.
- The report is guarded by `#[Middleware('auth')]` and `#[Authorize('view-operator-report')]`; the
  Gate allows only `is_admin === true`. A shared `isOperator` prop shows the sidebar and header entry.

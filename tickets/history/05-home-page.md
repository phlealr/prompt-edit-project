# 05. Home page

- Status: done
- Blocked by: 01
- Spec: docs/features/history.md

## What to build

A guest opening the home page sees a short page of our own instead of the starter kit's Laravel
welcome page: what the app does and how it charges, with Sign in and Register. Signed-in users still
land on Generate.

## Acceptance criteria

- [x] A guest opening `/` sees what the app does, how it charges, and links to sign in and register.
- [x] Nothing on the page comes from the starter kit's Laravel marketing content.
- [x] A signed-in user opening `/` still lands on Generate.

## Notes

- Decision (user, 2026-11-18): a short landing page of our own rather than redirecting guests to sign
  in.
- The app is named "Prompt Edit Project" (user, 2026-11-18): it is the default of `APP_NAME` in
  `config/app.php` and of `VITE_APP_NAME` in `resources/js/app.ts`; both variables still override it.
  Laravel Cloud sets `APP_NAME` to the app's slug, so set `APP_NAME="Prompt Edit Project"` there.
- The starter kit's Laravel logo is replaced by a plain image icon in `AppLogoIcon.vue`, used on the
  home page, the sidebar and the auth pages.
- Manual check on the local app, 2026-11-18: a guest sees the name, "Generate images from a prompt",
  the three steps and Create an account / Sign in; the tab title reads "… - Prompt Edit Project".

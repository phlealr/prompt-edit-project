# 0020. Logic lives in actions, in a layer-first folder structure grouped by area

- Status: accepted
- Date: 2026-11-17
- Supersedes: ADR 0013

## Context

ADR 0013 put all business logic in single-purpose classes behind thin controllers and jobs, and called
them "use cases". Laravel 13 prescribes no place for business logic: `app/` starts with `Http`,
`Models` and `Providers`, and `Console` and `Http` are entry points, not core logic. The dominant
Laravel convention for these classes is **actions** in `app/Actions/`, which Laravel's own starter
kits use (Fortify ships `app/Actions/Fortify/`). "Use case" comes from Clean Architecture and is rare
in Laravel projects. Laravel also already owns `app/Providers/` for service providers, which collides
with our term **Provider**. The user chose to stay close to Laravel's conventions rather than a
domain-first (DDD) layout.

## Decision

Everything in ADR 0013 stands, with these changes and additions:

- The logic classes are **actions**: `app/Actions/<Area>/<VerbNoun>.php` with one public `handle()`
  method and no `Action` suffix (`app/Actions/Generations/SubmitGeneration.php`). Fortify's
  `app/Actions/Fortify/` stays as the starter kit ships it.
- The folder structure is **layer-first**, with the same area subfolders in every layer. The areas
  are `Generations`, `Credits` and `Reports`.
- Provider code lives in `app/ImageProviders/`: the `ImageProvider` interface and the types that cross
  the boundary at its root, the boundary's exceptions in `Exceptions/`, and each implementation in
  its own subfolder (`Pixelforge/`).
- `Money` lives in `app/ValueObjects/`; statuses and failure reasons are enums in `app/Enums/`.
- Controllers, Form Requests, tests and Inertia pages mirror the areas:
  `app/Http/Controllers/<Area>/`, `app/Http/Requests/<Area>/`, `tests/Feature/<Area>/`,
  `tests/Unit/<Area>/` and `resources/js/pages/<area>/`.

Earlier ADRs (0010, 0014) say "use case"; read it as "action".

## Rules it creates

- All rules of ADR 0013, with "use case" read as "action".
- Every action is in `app/Actions/<Area>/`, has exactly one public method, `handle()`, and its class
  name is a verb and a noun without a suffix.
- Every controller is in `app/Http/Controllers/<Area>/` and every Form Request in
  `app/Http/Requests/<Area>/`, using the same area names as the actions.
- Provider code is only in `app/ImageProviders/`; Pixelforge-specific code is only in
  `app/ImageProviders/Pixelforge/`.
- `Money` is `App\ValueObjects\Money`; generation statuses and failure reasons are enums in
  `app/Enums/`.
- Models stay in `app/Models/`, where Laravel's generators put them.

## Consequences

The layout is what a Laravel developer expects, so Artisan generators, factories, policies and Boost's
guidelines work unchanged. One feature is spread across several top-level folders; the shared area
name in each layer is what keeps it findable.

## Alternatives considered

- **`app/UseCases/`**: matches the glossary as first written, but is rare in Laravel; a reviewer
  would look for `app/Actions/`.
- **Domain-first (DDD-lite, `app/Domain/<Area>/…`)**: keeps each area's code together, but works
  against Laravel's generators and auto-discovery, and the replaceability the user asked for already
  comes from ADR 0014's boundaries.
- **Full DDD (bounded contexts, aggregates, repositories)**: far more structure than this app needs.
- **`app/Providers/` for provider code**: collides with Laravel's service providers.

## In plain words

Business logic is in Laravel-style action classes, one job each, in `app/Actions/<Area>/`. Every
layer uses the same area folders, so a feature is easy to find, and Pixelforge lives on its own in
`app/ImageProviders/Pixelforge/`.

# 0041. Data is passed as typed objects, never as arrays used as objects

- Status: accepted
- Date: 2026-11-18

## Context

PHP arrays can stand in for objects (`['estimate' => …, 'balance' => …]`), but their keys are
unchecked strings, their shape lives only in a docblock, and the author must explain each one in a
live interview. The user banned arrays used as objects from the project.

## Decision

Our own code passes data as typed objects: readonly classes with named, typed properties. Data
classes live in `app/Data/<Area>/`; types that cross the provider boundary stay in
`app/ImageProviders/`, and a provider's own parsed types in its folder (`Pixelforge/`). Arrays remain
only as lists (`list<T>`) and where the framework requires them.

## Rules it creates

- No parameter, return value, property, cache value or page prop of ours is an array shape
  (`array{…}`) or an associative array carrying data; it is a typed object.
- Data classes are readonly classes in `app/Data/<Area>/`, except provider boundary types
  (`app/ImageProviders/`) and a provider's parsed types (`app/ImageProviders/<Provider>/`).
- Arrays are allowed as lists (`list<T>`) and at framework edges only: validation rules, model casts
  and multi-column attribute setters, config, the outer map of `Inertia::render()` props,
  `jsonSerialize()`, request payloads and factory attributes.
- A provider's decoded JSON is read once, where it arrives, and mapped straight into typed objects.

## Consequences

Every piece of data has a class whose properties the IDE and Larastan check, and which can be named
in the interview. There are more small classes, and serializing one for the frontend relies on its
public properties.

## Alternatives considered

- **Array shapes with PHPStan types**: fewer classes, but the shape is a comment, not code.
- **Ban arrays even at framework edges**: stricter, with API Resources and input objects everywhere;
  more classes to explain for no checked benefit.

## In plain words

Data moves through the code as small typed classes, never as arrays pretending to be objects. Arrays
are only lists, or what Laravel itself asks for.

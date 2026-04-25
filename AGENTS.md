# `lyre/lyre` Agent Guide

## Package Purpose
`lyre/lyre` is the core runtime package. It defines the conventions all other Lyre packages use for models, repositories, controllers, resources, policies, observers, helper discovery, tenancy hooks, and core commands.

## What Belongs In This Package
- Base primitives (`Model`, `Repository`, `Controller`, `Resource`, `Policy`, `Observer`).
- Core helpers in `src/helpers/helpers.php` used by multiple packages.
- Core config (`src/config/lyre.php`) and shared defaults.
- Generic scaffolding and cache commands in `src/Console/Commands`.
- Core tenancy support models/middleware.

## What Does Not Belong Here
- Domain-specific business logic (billing/content/commerce/school/etc.).
- Package-specific endpoints that can live in domain packages.

## Public API / Stable Contracts
- Helper function names and behavior used cross-package (`register_repositories`, `register_global_observers`, `__response`, `tenant`, model discovery helpers).
- `BaseModelTrait::generateConfig()` naming/discovery behavior.
- Response envelope semantics from `__response`.
- Base class method signatures used by package controllers/repositories/resources.
- Config keys under `lyre.*` used by package/service providers.

## Internal Areas That May Change
- Internal query-building implementation details in `Repository` if external behavior remains stable.
- Internal observer logging implementation details preserving side effects.

## Usage Rules
- Consumers should extend `Lyre\Model` (or include equivalent traits/contract behavior).
- Repository implementations should follow `*Repository` + interface naming to support auto-binding/helpers.
- Use `config('lyre.path.*')` arrays for discovery extensions rather than hardcoding class lookups.

## Extension Rules
- New generic helper behavior goes here only if shared by multiple packages.
- Preserve backward compatibility for helper names and base class signatures.
- When introducing new discovery conventions, update all dependent package providers and docs in same change.
- Do not change table-prefix behavior for `Lyre\` models without a major-version migration plan.

## Testing Requirements
- Any change touching helper discovery or base class behavior requires smoke verification in at least two dependent packages.
- Validate CRUD flow, serialization, policy checks, and observer registration in a consuming Laravel app.

## Docs To Update When This Package Changes
- Root [AGENTS.md](/Users/chegekigathi/Projects/packages/lyre-packages/AGENTS.md)
- [docs/architecture.md](/Users/chegekigathi/Projects/packages/lyre-packages/docs/architecture.md)
- [docs/package-responsibilities.md](/Users/chegekigathi/Projects/packages/lyre-packages/docs/package-responsibilities.md)
- `packages/lyre/README.md` and relevant docs under `packages/lyre/docs/`

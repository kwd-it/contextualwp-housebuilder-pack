# Developer notes (internal)

This document is for contributors to this repository. Keep wording professional and avoid committing any client-specific information.

## Core vs sector-pack boundary

- **ContextualWP core** remains sector-agnostic and provides extension points, including pack registration.
- **Sector packs** (this plugin) should:
  - Contain only sector-specific configuration and logic.
  - Avoid duplicating or forking core functionality.
  - Fail gracefully when core is missing or incompatible.

## Current scaffold status

Implemented:
- Composer PSR-4 autoloading for `ContextualWP\HousebuilderPack\`.
- Safe bootstrap and guarded registration flow.
- Pack registrar isolated in `src/PackRegistrar.php`.
- Service placeholders in `src/Services/`.
- Fixture structure and public safety policy.

Intentionally not implemented yet:
- Any real housebuilder schema, relationships, or interpretation logic.
- Tests and CI (beyond scaffold structure).
- Any assumptions about core internals beyond the registration function name and minimum version requirement.

## Testing workflow (local)

Recommended local loop:
- Use a real local WordPress site with ContextualWP core installed.
- Activate core, then activate this pack.
- Verify:
  - No fatals with core active/inactive.
  - Admin notices appear only for administrators when needed.
  - Pack registration occurs only when core is available and compatible.

## Fixture policy (non-negotiable)

- Never commit:
  - Real endpoint outputs
  - Real client identifiers, addresses, post content, or metadata
  - Any operational details that could reveal private environments
- `tests/local-fixtures/` is strictly local-only and is gitignored.
- `tests/fixtures/examples/` is reserved for future sanitised examples only.

## Local comparison loop

The intention is to support a simple baseline comparison during development:

- `tests/local-fixtures/baseline/`: captured outputs with core only
- `tests/local-fixtures/with-pack/`: captured outputs with core + this pack

Compare outputs locally to confirm pack behaviour is additive and safe.

## Next implementation goals

- Confirm ContextualWP core:
  - Whether it exposes a version constant (currently assumed `CONTEXTUALWP_VERSION`)
  - The required registration config keys for `contextualwp_register_sector_pack()`
  - Whether services are instantiated by core, and the expected service interface/lifecycle
- Once confirmed:
  - Add a minimal service interface alignment layer (only if required)
  - Implement schema extension scaffolding with real extension points
  - Add a minimal smoke-test harness (no fake tests)

# Developer notes (internal)

This document is for contributors to this repository. Keep wording professional and avoid committing any client-specific information.

## Core vs sector-pack boundary

- **ContextualWP core** remains sector-agnostic and provides extension points, including pack registration.
- **Sector packs** (this plugin) should:
  - Contain only sector-specific configuration and logic.
  - Avoid duplicating or forking core functionality.
  - Fail gracefully when core is missing or incompatible.

## Current scaffold status

Implemented:
- Composer PSR-4 autoloading for `ContextualWP\HousebuilderPack\`.
- Safe bootstrap and guarded registration flow.
- Pack registrar isolated in `src/PackRegistrar.php`.
- Service placeholders in `src/Services/`.
- Fixture structure and public safety policy.

Intentionally not implemented yet:
- Any real housebuilder schema, relationships, or interpretation logic.
- Tests and CI (beyond scaffold structure).
- Any assumptions about core internals beyond the registration function name and minimum version requirement.

## Testing workflow (local)

Recommended local loop:
- Use a real local WordPress site with ContextualWP core installed.
- Activate core, then activate this pack.
- Verify:
  - No fatals with core active/inactive.
  - Admin notices appear only for administrators when needed.
  - Pack registration occurs only when core is available and compatible.

## Fixture policy (non-negotiable)

- Never commit:
  - Real endpoint outputs
  - Real client identifiers, addresses, post content, or metadata
  - Any operational details that could reveal private environments
- `tests/local-fixtures/` is strictly local-only and is gitignored.
- `tests/fixtures/examples/` is reserved for future sanitised examples only.

## Local comparison loop

The intention is to support a simple baseline comparison during development:

- `tests/local-fixtures/baseline/`: captured outputs with core only
- `tests/local-fixtures/with-pack/`: captured outputs with core + this pack

Compare outputs locally to confirm pack behaviour is additive and safe.

## Next implementation goals

- Confirm ContextualWP core:
  - Whether it exposes a version constant (currently assumed `CONTEXTUALWP_VERSION`)
  - The required registration config keys for `contextualwp_register_sector_pack()`
  - Whether services are instantiated by core, and the expected service interface/lifecycle
- Once confirmed:
  - Add a minimal service interface alignment layer (only if required)
  - Implement schema extension scaffolding with real extension points
  - Add a minimal smoke-test harness (no fake tests)


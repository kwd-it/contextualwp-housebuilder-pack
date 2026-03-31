# ContextualWP Housebuilder Pack

Housebuilder sector pack plugin for ContextualWP.

This repository is the **first sector pack** for ContextualWP and is intended to be a **clean, public reference implementation** for industry-specific packs. ContextualWP core remains open source and sector-agnostic; sector packs are separate plugins that enhance core only when active.

## Why this exists

- Provide a production-quality plugin scaffold for sector packs.
- Demonstrate safe integration patterns with ContextualWP core (graceful dependency checks).
- Establish a public baseline for structure, docs, and fixture hygiene.

## Architecture overview

- **Main plugin file**: `contextualwp-housebuilder-pack.php`
  - Loads Composer autoloader (if present).
  - Boots the plugin safely (no fatals if dependencies are missing).
- **Bootstrap**: `src/Bootstrap.php`
  - Creates the plugin instance and registers hooks.
- **Compatibility**: `src/Compatibility.php`
  - Checks whether ContextualWP core is available.
  - Performs best-effort ContextualWP version detection.
- **Plugin**: `src/Plugin.php`
  - Runs guarded boot on `init`.
  - Shows lightweight admin notices when core is missing/incompatible.
- **Pack registration**: `src/PackRegistrar.php`
  - Isolates the pack registration call and registration config shape.
  - Intended integration point: `contextualwp_register_sector_pack()`.
- **Services**: `src/Services/*`
  - Lightweight placeholders for future sector functionality.

## Requirements

- WordPress: **6.4+**
- PHP: **8.1+**
- ContextualWP core: **1.1+**

## Installation

1. Place the plugin folder at:
   - `wp-content/plugins/contextualwp-housebuilder-pack/`
2. Install Composer dependencies:

```bash
composer install --no-dev
```

3. Activate **ContextualWP** core.
4. Activate **ContextualWP Housebuilder Pack**.

## Activation behaviour

- If **ContextualWP core is inactive/unavailable**:
  - The plugin will **not fatal** and will not affect front end behaviour.
  - An **admin notice** may be displayed for site administrators.
- If **ContextualWP core is active and compatible**:
  - The plugin will attempt registration via `contextualwp_register_sector_pack()` using a defensive wrapper.

## Compatibility notes

This plugin targets ContextualWP **v1.1+**.

Version detection is best-effort. This scaffold currently checks for a placeholder constant `CONTEXTUALWP_VERSION` if present; if core does not expose a version constant, the pack proceeds as compatible when the registration function exists.

## Fixture policy (public safety)

This repository must remain safe to publish publicly.

- `tests/fixtures/examples/` is reserved for **future sanitised** example fixtures only.
- `tests/local-fixtures/` is for local development and is **gitignored**.
- Real client data, real endpoint outputs, and any sensitive operational assumptions must **never** be committed.

See `tests/fixtures/README.md` for details.

## Development notes

See `DEVNOTES.md` for practical guidance on:
- Core vs pack boundaries
- Local testing workflow
- Fixture policy and comparison loop
- Next implementation goals

## Roadmap

- Confirm ContextualWP pack registration API shape (config keys, lifecycle, service registration).
- Implement schema extensions for housebuilder entities using core extension points.
- Add local-only fixture capture + comparison scripts (kept out of the public repo).
- Add minimal unit tests once core APIs are confirmed (no fake tests in the scaffold).

## Licence

GPL-2.0-or-later. See `LICENSE`.


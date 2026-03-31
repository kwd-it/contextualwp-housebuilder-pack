# ContextualWP Housebuilder Pack

Housebuilder sector pack plugin for ContextualWP.

This repository is the **first sector pack** for ContextualWP and remains a **clean, public reference implementation** for industry-specific packs. As of **0.2.0** it is no longer scaffold-only: it ships a **first functional enhancement pass** that improves manifest relationships, schema interpretation, and ACF field semantics for typical housebuilder-style WordPress setups. ContextualWP core stays sector-agnostic; this plugin enhances exported schema when active.

## Why this exists

- Provide a production-quality plugin structure for sector packs.
- Demonstrate safe integration with ContextualWP core (graceful dependency checks).
- Establish a public baseline for structure, docs, and fixture hygiene.
- Deliver **additive, rule-based** housebuilder hints without inspecting post content or field values.

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
  - Runs guarded boot on `plugins_loaded`.
  - Shows lightweight admin notices when core is missing/incompatible.
- **Pack registration**: `src/PackRegistrar.php`
  - Registers with `contextualwp_register_sector_pack()`.
  - Loads **schema extensions** when core is available (relationships, interpretation, ACF).
- **Services**: `src/Services/*`
  - **RelationshipService** — manifest relationship rows from CPTs, taxonomy registration, and optional ACF link evidence.
  - **InterpretationService** — `housebuilder_pack` metadata on post types (manifest + REST) and aggregate interpretation via `contextualwp_schema_interpretation`.
  - **SchemaExtensionService** — keyword-based ACF field `semantic` tags and `semantic_groups` roll-ups.
  - **SiteStructureHints** — read-only helpers (public CPT checks, taxonomy naming heuristics, ACF field graph inspection).

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
  - The plugin registers the sector pack and attaches filters that enrich manifest, REST schema, interpretation output, and ACF schema exports.

## Compatibility notes

This plugin targets ContextualWP **v1.1+**.

Version detection is best-effort: it checks for `CONTEXTUALWP_VERSION` when defined; if core does not expose a version constant, the pack treats the environment as compatible when the registration function exists.

## Current scope and limitations

Enhancements are **conservative** and driven by registration metadata and **common housebuilder naming** (for example public CPT slugs such as `developments`, `plots`, and `future_developments`, and taxonomies whose slugs look like a development/scheme classifier). ACF semantics use **substring keyword rules** on field name, label, and instructions only; ties or ambiguity result in no semantic assignment. Sites with different slugs or editorial models may see no or partial enrichment until inference is expanded.

## Fixture policy (public safety)

This repository must remain safe to publish publicly.

- `tests/fixtures/examples/` is reserved for **future sanitised** example fixtures only.
- `tests/local-fixtures/` is for local development and is **gitignored** (see `.gitignore` for patterns).
- Real client data, real endpoint outputs, and any sensitive operational assumptions must **never** be committed.

See `tests/fixtures/README.md` for details.

### Local fixture comparison workflow

For a repeatable local check that the pack only adds structured hints:

- Capture exports (for example manifest, schema, ACF schema) into `tests/local-fixtures/baseline/` with **ContextualWP core only**.
- Capture the same exports into `tests/local-fixtures/with-pack/` with **core + this pack** active.
- Diff the trees: expect new relationship rows, `housebuilder_pack` blocks, interpretation subtrees, and ACF `semantic` / `semantic_groups` fields where structure matches the rules above—not changes to unrelated core shape.

## Development notes

See `DEVNOTES.md` for practical guidance on:

- Core vs pack boundaries
- What changed in the 0.2.0 pass and remaining assumptions
- Fixture policy and comparison loop
- Likely next steps

## Roadmap

- Broaden structure detection beyond exact slug matches where safe (for example configurable slug maps or additional heuristics).
- Review and tighten semantic keyword lists and tie-breaking to reduce false positives/negatives.
- Confirm long-term core API contracts (filters, interpretation merge rules, service lifecycle).
- Add sanitised public examples under `tests/fixtures/examples/` when appropriate.
- Add minimal automated tests once core contracts are stable.

## Licence

GPL-2.0-or-later. See `LICENSE`.

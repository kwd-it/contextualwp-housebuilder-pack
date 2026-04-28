# ContextualWP Housebuilder Pack

Housebuilder sector pack plugin for ContextualWP.

This repository is the **first sector pack** for ContextualWP and remains a **clean, public reference implementation** for industry-specific packs. Through **0.4.0** it ships **additive, rule-based** enhancements: manifest relationships, REST and manifest schema interpretation, aggregate interpretation output, ACF field semantics, and a **read-only authenticated plot dataset REST endpoint** for typical housebuilder-style WordPress setups. **Structural entity detection goes beyond exact CPT slug matches** (see below). ContextualWP core stays sector-agnostic; this plugin enriches exported schema when active.

## Why this exists

- Provide a production-quality plugin structure for sector packs.
- Demonstrate safe integration with ContextualWP core (graceful dependency checks).
- Establish a public baseline for structure, docs, and fixture hygiene.
- Deliver **additive, rule-based** housebuilder hints without inspecting post content or field values.

## Architecture overview

- **Main plugin file**: `contextualwp-housebuilder-pack.php`
  - Loads the plugin-local Composer autoloader when `vendor/autoload.php` is present, or relies on the site’s root autoloader when the pack is required as a Composer package.
  - Boots the plugin safely (no fatals if pack classes cannot be loaded).
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
  - Loads **schema extensions** when core is available (relationships, interpretation, ACF) and registers pack REST routes.
- **Services**: `src/Services/*`
  - **RelationshipService** — manifest relationship rows from detected development-like, plot-like, and pipeline-like CPTs, taxonomy registration, and optional ACF link evidence.
  - **InterpretationService** — `housebuilder_pack` metadata on post types (manifest + REST) and aggregate interpretation via `contextualwp_schema_interpretation`.
  - **SchemaExtensionService** — keyword-based ACF field `semantic` tags and `semantic_groups` roll-ups.
  - **PlotsRestService** / **PlotDatasetMapper** — `GET /wp-json/contextualwp-housebuilder/v1/plots`: read-only, authenticated (default capability `edit_posts`, filterable), **published** plot-like posts; public monitoring fields only. Query `page` and `per_page` (default **500**, max **500**). Filter **`contextualwp_housebuilder_plot_meta_key_candidates`** supplies ordered meta key candidates per logical field for site-specific ACF/meta naming. Response objects include `id`, `wp_id`, `title`, `status`, `price`, `bedrooms`, `development`, `house_type`, `url`, and `last_updated` where available.
  - **SiteStructureHints** — read-only helpers (public CPT lists by role, taxonomy rules, ACF field graph inspection).
  - **HousebuilderStructuralSignals** — normalised token-based classifiers for CPTs and taxonomies; conservative synonyms and exclusion rules (no content inspection).

**Current focus areas** for this pack: **relationships** (manifest edges and evidence), **schema interpretation** (post types and taxonomies), **ACF semantics** (field-level tags), **plot dataset REST** (monitoring exports for authenticated clients), and **broader structural detection** (registration metadata and naming signals, not values).

## Requirements

- WordPress: **6.4+**
- PHP: **8.1+**
- ContextualWP core: **1.1+**

## Installation

Supported setups:

1. **Built release / manual install** — ZIP or copied plugin tree that includes **`vendor/`** (run `composer install --no-dev` in the pack when building the artifact). Place the folder under `wp-content/plugins/contextualwp-housebuilder-pack/` and activate.
2. **Composer-managed WordPress project** — Require this package from the **project root** (for example `composer require kwd-it/contextualwp-housebuilder-pack`) so the root `vendor/autoload.php` registers the pack’s PSR-4 classes. **Do not** rely on running Composer only inside the plugin directory; the plugin may have no local `vendor/autoload.php`, which is expected when the root autoloader already loads the pack.

Then activate **ContextualWP** core and **ContextualWP Housebuilder Pack** (order does not change behaviour: the pack waits for core on `plugins_loaded`).

If wp-admin shows that the pack **could not load** (classes missing), fix the Composer or release layout—not the same as “ContextualWP core is inactive”, which is a separate admin notice from `Plugin.php`.

## Activation behaviour

- If **pack PHP classes cannot be autoloaded** (neither plugin-local `vendor/autoload.php` nor the project autoloader provides `ContextualWP\HousebuilderPack\Bootstrap`):
  - The plugin will **not fatal** and will not affect front end behaviour.
  - An **admin notice** explains project-level Composer or a built release with `vendor/`.
- If **ContextualWP core is inactive/unavailable**:
  - The plugin will **not fatal** and will not affect front end behaviour.
  - A **different** admin notice refers to installing/activating ContextualWP core (not “run Composer in the plugin folder”).
- If **ContextualWP core is active and compatible**:
  - The plugin registers the sector pack and attaches filters that enrich manifest, REST schema, interpretation output, and ACF schema exports.

## Compatibility notes

This plugin targets ContextualWP **v1.1+**.

Version detection is best-effort: it checks for `CONTEXTUALWP_VERSION` when defined; if core does not expose a version constant, the pack treats the environment as compatible when the registration function exists.

## Current scope and limitations

Enhancements are **conservative** and driven by registration metadata and **normalised naming signals** (CPT slugs and labels, taxonomy slugs and labels). Detection recognises **development-like**, **plot- or unit-like**, **pipeline- or forthcoming-scheme-like**, and **house-type- or property-model-like** public post types when token and compact-slug evidence is strong enough, with **exclusion tokens** to reduce false positives on editorial or careers-style CPTs. Taxonomy treatment for plot classifiers is **stricter**: development/scheme-style signals must align with slug tokens (including guarding label-only matches that could otherwise misread words such as “site”). ACF semantics still use **substring keyword rules** on field name, label, and instructions only; ties or ambiguity result in no semantic assignment. Sites with unusual naming may see partial or no enrichment until rules are extended.

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
- Optionally use **versioned subdirectories** (for example `with-pack/v1`, `with-pack/v2`) to compare one enhancement pass or release milestone against the next; those artefacts remain **local-only** under the gitignore rules unless later **sanitised** into `tests/fixtures/examples/`.
- Diff the trees: expect new relationship rows, `housebuilder_pack` blocks, interpretation subtrees, and ACF `semantic` / `semantic_groups` fields where structure matches the rules above—not changes to unrelated core shape.

## Development notes

See `DEVNOTES.md` for practical guidance core vs pack boundaries, what changed in recent passes, fixture policy, and likely next steps.

## Roadmap

- Second-site and variant layouts for portability validation.
- Richer relationship hints where evidence supports house types and cross-links.
- Media, download, and floor-plan oriented ACF semantics where patterns are clear enough to stay low–false-positive.
- Confirm long-term core API contracts (filters, interpretation merge rules, service lifecycle).
- Add sanitised public examples under `tests/fixtures/examples/` when appropriate.
- Add minimal automated tests once core contracts are stable.

## Licence

GPL-2.0-or-later. See `LICENSE`.

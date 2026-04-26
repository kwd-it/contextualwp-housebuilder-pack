# Developer notes (internal)

This document is for contributors to this repository. Keep wording professional and avoid committing any client-specific information.

## Core vs sector-pack boundary

- **ContextualWP core** remains sector-agnostic and provides extension points, including pack registration and schema filters.
- **Sector packs** (this plugin) should:
  - Contain only sector-specific configuration and logic.
  - Avoid duplicating or forking core functionality.
  - Fail gracefully when core is missing or incompatible.

## Status: 0.3.1 (Composer-managed autoload patch; structural detection pass below is 0.3.0)

Implemented (cumulative):

- Composer PSR-4 autoloading for `ContextualWP\HousebuilderPack\`.
- Safe bootstrap and guarded registration flow.
- Pack registrar in `src/PackRegistrar.php` plus extension hookup after successful registration.
- **`HousebuilderStructuralSignals`** (`src/Services/HousebuilderStructuralSignals.php`): normalisation (`normalizeSlug`, token splitting), compact alphanumeric slug checks, **concept roots** for development/site/scheme/community, strong plot/unit/property tokens, pipeline modifiers and compact pipeline slugs, house-type/property-model compact forms and paired label+slug rules, **post-type exclusion tokens**, ordered `postTypeInterpretationProfile()`, and **taxonomy** blocklist plus `taxonomySuggestsDevelopmentSchemeClassifier()` with slug-token corroboration for label matches.
- **`RelationshipService`**: `contextualwp_manifest_schema_relationships` — edges between detected **development-site-like** and **plot-like** CPTs; optional ACF `relationship` / `post_object` evidence; taxonomy→plot rows when `isPlotDevelopmentClassifierTaxonomy()` passes; **pipeline-like**→development-like when both sets are non-empty.
- **`InterpretationService`**: `contextualwp_schema` and `contextualwp_manifest_schema` — `housebuilder_pack` blocks for every public CPT that receives a structural profile; `contextualwp_schema_interpretation` — aggregate `housebuilder_pack` with post type and taxonomy maps (including `plot_development_classifier` only under the stricter taxonomy path).
- **`SchemaExtensionService`**: `contextualwp_acf_schema_field_groups` — per-field `semantic` and `semantic_groups` as in 0.2.0.
- **`SiteStructureHints`**: delegates public CPT discovery and taxonomy helpers to **HousebuilderStructuralSignals**; `plotLikePublicPostTypeSlugs()`, `developmentSiteLikePublicPostTypeSlugs()`, `pipelineDevelopmentLikePublicPostTypeSlugs()`, `isGenericDevelopmentTaxonomy()` / `isPlotDevelopmentClassifierTaxonomy()`, ACF graph walk unchanged.

**This iteration (0.3.0)** in plain terms:

- Replaced reliance on **fixed CPT slugs** with **token- and pattern-based** detection grounded in registration strings, still **without reading content or field values**.
- **Broadened** coverage was deliberately **tightened** after false-positive review: exclusions, pipeline vs development disambiguation, slug corroboration for taxonomy label hints, and house-type detection that requires slug support (labels alone are insufficient, mirroring the bar for plot-like inference).

Intentionally not implemented yet:

- Content-level or value-level inference (no reading posts, terms, or field values for semantics).
- Automated test suite in this repo.
- Site-configurable CPT slug maps or filters (integrators would need to fork or propose a core/pack hook).

## Assumptions (important)

- **Detection** is driven by **public** post types and taxonomies plus ACF field **definitions** (for link evidence and semantics), not runtime content.
- **Taxonomy hints** for plot classifiers: taxonomy must apply **only** to CPTs classified as plot-like **and** pass `taxonomySuggestsDevelopmentSchemeClassifier()` including stricter slug rules when labels are involved.
- **ACF evidence** for dev↔plot relationships requires ACF loaded and field groups discoverable via `acf_get_field_groups` / `acf_get_fields`; link detection only considers `relationship` and `post_object` types whose `post_type` filter includes the target CPT.
- **Semantic tagging** is substring keyword matching on **name, label, instructions**; two groups with the same top score produce **no** semantic output for that field.
- Version and compatibility behaviour unchanged in spirit from 0.1.0: still depends on `contextualwp_register_sector_pack()` presence and optional `CONTEXTUALWP_VERSION` for strict checks.

## Bootstrap / autoload verification (manual)

Confirm the main plugin file behaves for all three cases (wp-admin as a user with `activate_plugins`):

1. **Built install** — Plugin directory includes `vendor/autoload.php` (from a release build or `composer install` in the pack when producing the artifact). Activating the plugin should **not** show the yellow “could not load” notice; core missing still shows the separate **ContextualWP core** notice from `Plugin.php` after `plugins_loaded`.
2. **Composer-managed site** — No `vendor/` under the plugin path, but the project root `vendor/autoload.php` already requires this package (`kwd-it/contextualwp-housebuilder-pack`) so `ContextualWP\HousebuilderPack\Bootstrap` is registered. Activating should behave like (1); you must **not** see a false warning about missing `vendor/autoload.php` in the plugin folder.
3. **Missing classes** — Remove the pack from the root `composer.json` / autoload mapping (or use an incomplete tree) while the plugin remains active. Expect the single warning: install via **project** Composer or use a **built release** with dependencies—not instructions to run Composer inside the plugin directory.

## Testing workflow (local)

Recommended local loop:

- Use a real local WordPress site with ContextualWP core installed.
- Activate core, then activate this pack.
- Verify:
  - No fatals with core active/inactive.
  - Admin notices appear only for administrators when needed.
  - Pack registration and filters run only when core is available and compatible.
- Optionally run the **baseline vs with-pack** fixture capture (see below) and diff JSON exports; versioned subfolders under `with-pack/` help compare milestones locally.

## Fixture policy (non-negotiable)

- Never commit:
  - Real endpoint outputs
  - Real client identifiers, addresses, post content, or metadata
  - Any operational details that could reveal private environments
- `tests/local-fixtures/` is strictly local-only; JSON/NDJSON/TXT there are gitignored.
- `tests/fixtures/examples/` is reserved for future sanitised examples only.

## Local comparison loop

Use a simple baseline comparison during development:

- `tests/local-fixtures/baseline/`: captured outputs with core only
- `tests/local-fixtures/with-pack/`: captured outputs with core + this pack

Compare locally to confirm pack behaviour is additive (new keys/branches, not breaking changes to core-owned structure). Do not commit captured files unless sanitised for `tests/fixtures/examples/`.

## Next steps (likely)

- **Second-site portability testing** on additional real stacks (naming variants, plugin mixes) without embedding site-specific rules in the public pack.
- **Richer house type relationships** in the manifest when ACF or taxonomy evidence is unambiguous enough.
- **Media / download / floorplan** interpretation and ACF semantic coverage where keyword and structure patterns are clear and false-positive risk stays low.
- Align with any new core hooks or interpretation merge conventions.
- Add a minimal smoke-test harness or sanitised fixtures when contracts stabilize.

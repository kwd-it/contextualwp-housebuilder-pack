# Changelog

All notable changes to this project will be documented in this file.

## 0.4.1 - 2026-04-29

### Fixed

- **`GET /wp-json/contextualwp-housebuilder/v1/plots`**: the **`limit`** query parameter is now applied (same **500** maximum as **`per_page`**). **`per_page`** is unchanged; when both appear in the query string, **`per_page`** takes precedence. Non-numeric or empty values for **`limit`** / **`per_page`** in the query fall back to the default page size; numeric values clamp to **1–500**. Response shape unchanged.

### Added

- PHPUnit coverage for plot list pagination resolution (`PlotsRestQueryLimits`); run **`composer test`** or **`vendor/bin/phpunit`**.

## 0.4.0 - 2026-04-28

### Added

- Read-only authenticated REST endpoint **`GET /wp-json/contextualwp-housebuilder/v1/plots`** (for example Contextual Console): published **plot-like** posts only, public monitoring fields (`id`, `wp_id`, `title`, `status`, `price`, `bedrooms`, `development`, `house_type`, `url`, `last_updated` where available; `null` when missing).
- Pagination query args `page` and `per_page` (default **500**, maximum **500**); response is a JSON array only (no total count in the body).
- Filter **`contextualwp_housebuilder_plot_meta_key_candidates`** — ordered post meta key lists per logical field for site-specific naming.
- Filter **`contextualwp_housebuilder_rest_plots_capability`** — override default **`edit_posts`** permission (aligned with ContextualWP editor-facing REST patterns).

## 0.3.1 - 2026-04-25

### Fixed

- Supported Composer-managed WordPress installs where the sector pack is autoloaded by the project/root Composer autoloader rather than plugin-local `vendor/autoload.php`.
- Improved the missing-class admin notice so it no longer incorrectly tells Composer-managed installs to run Composer inside the plugin directory.

### Documentation

- Clarified supported install modes for built release/manual installs and Composer-managed WordPress projects.
- Added manual bootstrap/autoload verification notes.

## 0.3.0 - 2026-04-08

Broadened, still **conservative** structural inference for housebuilder-style registrations; stricter taxonomy classifier handling; optional house-type / property-model detection when slug and label evidence is strong.

- **Structural detection**: Post types are classified using **normalised slug tokens**, compact slug forms, and selected **label-derived tokens** (no post or field values). Supports development-like, plot- or unit-like, pipeline-like, and (with a high bar) house-type- or property-model-like roles. **Exclusion tokens** on slugs suppress careers, blog, and similar CPTs from housebuilder inference.
- **Interpretation order**: Pipeline-like content takes precedence over house-type, development, and plot roles when multiple signals could apply.
- **Taxonomy classifier**: Plot–development classifier rows and taxonomy interpretation require taxonomies that **apply only to detected plot-like CPTs** and pass **stricter** development/scheme/site/community naming checks; label-derived matches require corroborating **slug tokens** to avoid substring false positives (for example “site” inside unrelated words).
- **Relationships**: Manifest relationship discovery uses the same detected endpoint sets (not a fixed trio of slugs), including pipeline→development edges where both sides are detected.

## 0.2.0 - 2026-03-31

First functional enhancement pass focused on structure consumers (manifest, REST schema, interpretation aggregate, ACF schema).

- **Manifest relationships**: Extends `contextualwp_manifest_schema_relationships` with rule-based edges for common housebuilder CPTs (`developments`, `plots`, `future_developments`), optional ACF relationship/post_object evidence between developments and plots, and conservative taxonomy→plot rows when a taxonomy matches generic “development” naming and applies only to plots.
- **Post type interpretation**: When matching public CPTs exist, adds a `housebuilder_pack` block to manifest and REST `post_types` entries with `entity_kind`, `summary`, and `typical_use` (or equivalent taxonomy roles).
- **Aggregate interpretation**: Contributes a `housebuilder_pack` subtree via `contextualwp_schema_interpretation` (post type and taxonomy interpretation maps for consumers that read the combined interpretation object).
- **ACF semantics**: Extends exported ACF field group data with per-field `semantic` objects (keyword substring rules on name, label, instructions) and rolled-up `semantic_groups` per group when unambiguous; ambiguous or tied matches emit no semantic tag.

Conservative behaviour: post types and taxonomies are detected from registration data and naming heuristics, not from content values. Sites that use different CPT slugs or taxonomy patterns may receive fewer or no automatic hints until inference is broadened.

## 0.1.0 - 2026-03-31

- Initial public scaffold for the ContextualWP Housebuilder sector pack.
- Safe bootstrap with graceful handling when ContextualWP core is missing or incompatible.
- Placeholder pack registration and service structure for future iteration.

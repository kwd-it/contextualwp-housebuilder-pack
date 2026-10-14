# Repository instructions

ContextualWP Housebuilder Pack is a WordPress sector pack plugin for ContextualWP core: relationship hints, schema interpretation, ACF semantic tagging, and a read-only authenticated plots REST feed (`GET /wp-json/contextualwp-housebuilder/v1/plots`) that Contextual Console consumes. Ecosystem-wide governance lives in [kwd-it/contextual-stewardship](https://github.com/kwd-it/contextual-stewardship). This file covers only what is local to this repository.

## Architecture and boundaries

- `contextualwp-housebuilder-pack.php` holds the plugin header, `CONTEXTUALWP_HOUSEBUILDER_PACK_VERSION` and `CONTEXTUALWP_HOUSEBUILDER_PACK_MIN_CONTEXTUALWP_VERSION`. Code lives in `src/` (PSR-4 `ContextualWP\HousebuilderPack\`), services in `src/Services/`.
- The pack extends core only through `contextualwp_register_sector_pack()` and documented filters, must fail gracefully when core is missing or incompatible, and never forks or patches core. Boundaries and assumptions: `DEVNOTES.md`.
- The plots feed is a cross-project interface. Contextual Console relies on its field names, the `present` / `missing` / `unknown` completeness enum, pagination (`per_page` default and cap 500) and the `edit_posts` default permission. Changes to it are additive unless an approved proposal says otherwise; name the Console impact in the pull request.
- Structural detection and semantic tagging use registration metadata and ACF field definitions only, never post content or field values. The plots feed completeness signals are the deliberate exception: they read stored plot meta and floor-plan block data in `post_content` to report `present` / `missing` / `unknown`.

## Platform

- WordPress 6.4 or later (tested up to 6.8), PHP 8.3 or later, ContextualWP core 1.3.3 or later. The plugin header, `composer.json`, the minimum core constant and `README.md` must agree.

## Conventions

- Branch prefixes `feat/`, `fix/`, `chore/`; one intent per branch; Conventional Commits; linear history.
- `CHANGELOG.md` gets an entry per change under an `Unreleased` heading until the release commit renames it.
- Fixture policy (`DEVNOTES.md`, `README.md`) is non-negotiable: no real client identifiers, endpoint outputs or operational details in tests, docs, commits or pull requests. `tests/local-fixtures/` is gitignored and stays local.
- Pull requests use `.github/PULL_REQUEST_TEMPLATE.md`.

## Validation

- `composer install`, then `composer test` (PHPUnit 10.5 with stubs in `tests/stubs/`; no WordPress install is needed). Run `composer validate` after any `composer.json` change.
- There is no CI. The local test run is the gate.

## Git and release boundaries

- Default branch `main`; no staging branch. This repository is public.
- Landing rule for Claude and Codex sessions: work on a dedicated task branch; implement and validate; run the final Codex review of the actual diff; resolve material findings; push the branch and open a pull request against `main` whose description carries `Owner summary:`, `Reviewed head:` and `Merge:` lines as the stewardship development workflow requires.
- Do not merge the pull request, except that a documentation and test-only maintenance pull request meeting every condition of the stewardship autonomous merge policy (D-016, `governance/authority-model.md` in kwd-it/contextual-stewardship) is merged by the steward through that repository's `tooling/merge/autonomous-merge.mjs`, which refuses anything outside Markdown, `docs/` and `tests/`. Merging this default branch deploys nothing: the product owner deploys by hand. Do not bump the version (header or constant), tag or publish a release unless separately authorised. Releases are `chore(release): prepare vX.Y.Z` commits on `main` by the product owner.

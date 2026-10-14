# Codex review guidance

Repository-specific priorities for reviewing changes to the ContextualWP Housebuilder Pack. Universal review behaviour is defined outside this repository; ecosystem governance is in [kwd-it/contextual-stewardship](https://github.com/kwd-it/contextual-stewardship).

## Review priorities

1. **Plots feed contract.** `GET /wp-json/contextualwp-housebuilder/v1/plots` in `src/Services/`: field names, the `present` / `missing` / `unknown` completeness enum, pagination limits and the permission callback. Contextual Console consumes this feed; flag any non-additive change or any change not named in the pull request as a Console impact.
2. **Public repository safety.** No real client identifiers, addresses, post content, endpoint outputs or operational details in tests, fixtures, docs or commits (fixture policy in `DEVNOTES.md`). Flag anything that looks site-specific, including hardcoded IDs or one-off values.
3. **Graceful degradation.** The plugin must not fatal when core is missing, incompatible or when classes cannot be autoloaded; the two admin notices stay distinct. Version checks against `CONTEXTUALWP_VERSION` keep their best-effort semantics.
4. **Detection accuracy.** Structural detection and semantic tagging use registration metadata and ACF definitions only. Flag false-positive risk from broadened token rules and any reading of post content or field values for semantics.
5. **Tests.** Behaviour changes come with PHPUnit coverage in `tests/` against the stubs, and `composer test` passes under the declared PHP minimum.

## Metadata consistency

WordPress, PHP and minimum core versions appear in the plugin header, `composer.json`, the minimum core constant and `README.md`. Flag any change that leaves them disagreeing.

## Normally ignore

`vendor/`, `.phpunit.cache/`, `tests/local-fixtures/`, and `composer.lock` beyond its content hash when `composer.json` metadata changes.

## Landing

A change is complete when it sits on a task branch with a pull request against `main`. Merging belongs to the product owner, except documentation and test-only maintenance merged by the steward under the stewardship policy D-016 through its `tooling/merge/autonomous-merge.mjs`; flag any pull request presented as eligible for that policy whose diff touches anything outside Markdown, `docs/` and `tests/`, weakens a test, or whose description lacks a `Reviewed head:` line matching the head commit. Version bumps (header and constant), release headings in `CHANGELOG.md` and tags belong to the product owner; flag any diff that performs them without explicit authorisation in the task.

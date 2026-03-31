## Fixtures policy

This repository is public. Fixtures must remain safe and generic.

### `examples/`

- Reserved for **future sanitised examples** only.
- Must not contain any real client data or real endpoint outputs.
- Use synthetic IDs, domains, and content.

### `local-fixtures/` (local only)

- Used for local development on a real local WordPress site.
- **Gitignored** by policy.
- Real endpoint outputs (JSON/NDJSON/text) must never be committed.

### Baseline vs with-pack comparison

For a simple local comparison loop:

- `local-fixtures/baseline/`: capture outputs with ContextualWP core only
- `local-fixtures/with-pack/`: capture outputs with core + this pack enabled


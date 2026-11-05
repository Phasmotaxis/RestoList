# Agent Rule: Changelog Maintenance

- **Root Navigation**: [Project Overview](../../README.md)
- **Target Changelog**: [Project Changelog](../../docs/CHANGELOG.md)
- **Architecture Context**: [Architecture Decision Records](../../docs/dev/specifications/architecture.md)

---

## Directives
1. Strict adherence to [Keep a Changelog v1.1.0](https://keepachangelog.com/en/1.1.0/) and [Semantic Versioning v2.0.0](https://semver.org/spec/v2.0.0.html).
2. Maintain the mandatory HTML/Markdown header in `docs/CHANGELOG.md`.
3. Subsections under `## [Unreleased]` must only appear if populated: `### Added`, `### Changed`, `### Deprecated`, `### Removed`, `### Fixed`, `### Security`.
4. Use past-tense verbs for entries and backtick all identifiers (`file`, `method`, `variable`).
5. Cross-reference corresponding ADR sections in `docs/dev/specifications/architecture.md` and feature contracts.

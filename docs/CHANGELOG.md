<h1 align="center" style="font-weight: bold">
    Changelog
</h1>

This software uses [Semantic Versioning v2.0.0](https://semver.org/spec/v2.0.0.html). This changelog is based on [keepachangelog.com v1.1.0](https://keepachangelog.com/en/1.1.0/).

**Types of Changes**

- `Added` for new features.
- `Changed` for changes in existing functionality.
- `Deprecated` for soon-to-be removed features.
- `Removed` for now removed features.
- `Fixed` for any bug fixes.
- `Security` in case of vulnerabilities.

## [Unreleased]

### Added

- Initialized repository directory structure matching Section 2 specification in [`docs/dev/specifications/architecture.md`](dev/specifications/architecture.md).
- Created root [`README.md`](../README.md) with bidirectional navigation links to operational runbooks and specifications.
- Added agent governance rules under [`.agents/rules/`](../.agents/rules/) enforcing non-interactive shell execution, explicit Git staging, and documentation sync.
- Defined sub-task execution contracts `task-001-scaffolding.contract.md` and `task-002-docker.contract.md` in [`docs/dev/ai/tasks.md`](dev/ai/tasks.md).
- Implemented multi-stage containerization in [`docker/php/Dockerfile`](../docker/php/Dockerfile), [`docker/nginx/nginx.conf`](../docker/nginx/nginx.conf), and [`docker-compose.yaml`](../docker-compose.yaml) for isolated local development.
- Authored air-gap image archiving utility in [`dev/scripts/sh/pre-cache.sh`](../dev/scripts/sh/pre-cache.sh) for defense against connectivity loss.
- Configured task orchestration runner in [`justfile`](../justfile) with `setup`, `build`, `up`, `down`, `test`, and `pre-cache` targets.
- Added MIT license terms in [`LICENSE`](../LICENSE), [`docs/LICENSE.md`](LICENSE.md), [`docs/licenses/MIT.md`](licenses/MIT.md), and attribution ledger in [`docs/NOTICE.md`](NOTICE.md).
- Authored dynamic YAML metadata parser in [`src/app/Views/partials/header.php`](../src/app/Views/partials/header.php) consuming [`dev/values/constants/main.yaml`](../dev/values/constants/main.yaml) for unified application branding.
- Implemented Live Panel Sabotage verification suite in [`dev/scripts/sh/test-panel-sabotage.sh`](../dev/scripts/sh/test-panel-sabotage.sh) asserting error elevation, visual diagnostic rendering, and syntax integrity.
- Expanded `SuperAdmin` authority in [`src/app/Controllers/SuperAdminController.php`](../src/app/Controllers/SuperAdminController.php) to govern cross-tenant organizations, administrators, users, and audit records under [ADR-006](dev/specifications/architecture.md).

### Changed

- Updated canonical [`.gitignore`](../.gitignore) to enforce user-defined and template-level path exclusions.
- Integrated automated panel sabotage verification into [`justfile`](../justfile) `test` target.

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

- Added complete Restaurant Client ERP controller and view suite for Userspace 2 per `docs/SPECIFICATION.md` Section 2.2.B and Section 4:
  - `DiningZonesController` & `DiningTablesController`: Floor plan management, semantic grid layouts, and real-time status mapping.
  - `ReservationController`: 15-minute boundary snapping, multi-table bookings, and double-booking prevention via `TableAllocator`.
  - `WaitlistController`: Walk-in party queue with automated cancellation backfilling and table reassignment.
  - `MenuItemController`: Menu catalog and real-time 86-list toggling with kitchen-specific permissions.
  - `StaffRBACController`: Multi-tenant staff directory, invite tokens, role updating, and strict privilege escalation defenses.
  - `AnalyticsController`: KPI metrics including table turnover, peak hour distribution, no-show rates, and integer centavos revenue.
  - `ReviewModerationController`: Customer review moderation queue, spam heuristics, and manager response system.
  - `RestaurantController`: Tenant restaurant provisioning and settings management.
- Added dynamic route parameter parsing (`:param`) and HTTP `PUT`, `PATCH`, `DELETE` methods in native `Router.php` and `Request.php`.
- Added 8 integration tests to `tests/runner.php` covering all Client ERP subdomains with 100% pass rate.
- Added tenant-scoped restaurant ERP operations for dining zones, tables, reservation intake, and reservation administration.
- Added customer restaurant browsing, menu detail, and CSRF-protected reservation booking routes.

### Changed

- Repaired conflicted dashboard views while preserving strict typing, CSRF fields, and semantic CSS classes.
- Changed `APP_DEBUG` default from `true` to `false` in `.env.example` and `docker-compose.yaml` for production-safe defaults; developers must explicitly opt in to debug mode.

### Fixed

- Replaced MySQL-only feature-flag upserts and timestamp expressions in reservation, audit, stock, and deposit services with SQLite-compatible statements.
- Fixed reservation overlap detection to fetch from the prepared statement after executing it.
- Removed hardcoded test password `Password123!` from tracked specimen `docs/specimens/screenshots/09_admin_users.html` (line 74).

### Security

- Expanded `dev/scripts/sh/test-panel-sabotage.sh` with test 5/5 to verify `APP_DEBUG=false` suppresses stack traces and exception details in HTTP 500 responses, enabling production hardening validation.
- Documented targeted account lockout (3-day hold) as a known design trade-off in `docs/GETTING-STARTED.md`, explaining the restaurant ERP security-availability balance: per-email rate limit (5 failures/15min) blocks brute force without emergency database intervention, per-IP limit (10 failures/15min) prevents collateral lockout on shared IPs.
- Verified `App\Core\LoginThrottle` dual-scope rate limiting: per-IP (10 failures/15min) and per-email (5 failures/15min) tracked separately, with only per-email counter cleared on successful login to deter distributed attacks.

### Security

- Expanded `dev/scripts/php/test-hardening.php` from 22 to 82 unit and source-level assertions, covering SQL injection payloads on sort, filter and search parameters, a grep check for forbidden calls in `src/`, XSS escaping of script and attribute payloads, mass assignment on `is_active` and `role_mask`, cross-tenant access through `AdminController::findManageableUser`, and `LoginThrottle::resolveClientIp` trusted-proxy handling. HTTP-level checks are not included.
- Enforced strict air-gap compliance in `src/public/index.php` Content-Security-Policy header by removing external font declarations in favor of `'self'`.
- Removed the hardcoded default password from `AdminController::createUser`; the admin now enters a password of 12 to 72 bytes, hashed with `password_hash`.
- Added per-session CSRF tokens in `Session::csrfToken` and `Session::csrfField`, and enforced them with `Session::verifyCsrf` on every POST handler, which returns HTTP 403 on mismatch.
- Called `session_regenerate_id(true)` through `Session::regenerate` after a successful login to prevent session fixation.
- Removed the seeded `Password123!` accounts from `init_seed.sql` and from `Database::bootstrapSqliteSchema`, so a fresh install creates no user rows.
- Added `src/database/bootstrap_superadmin.php`, a command-line-only script that creates the first SuperAdmin from `SUPERADMIN_EMAIL` and `SUPERADMIN_PASSWORD`, and leaves an existing `password_hash` unchanged.
- Changed `Session::start` to `SameSite=Strict`, `Secure` when served over HTTPS, and `use_strict_mode`, so the session cookie is not sent on cross-site requests.
- Added `App\Core\LoginThrottle`, which refuses login after 5 failed attempts per client IP or per email within 15 minutes, and returns HTTP 429.
- Changed `AuthController::login` to verify unknown emails against a constant bcrypt hash and to return the same `Invalid email or password.` message for unknown emails and wrong passwords.
- Added `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options` and `Referrer-Policy` headers in `public/index.php`, with no inline script or style allowed.
- Changed `ErrorHandler::renderDiagnosticScreen` to show the stack trace and source only when `APP_DEBUG` is exactly `true`, and to log the full detail with a `request_id` through `error_log`.
- Changed `views/errors/500.php` to show a generic message and the `request_id` in production.
- Removed the `GET /logout` route, so logout requires a `POST` with a CSRF token.
- Moved the debug screen's inline `<style>` block to `public/assets/css/diagnostic.css`.

### Added

- Created `dev/scripts/py/check_links.py` to recursively validate Markdown link resolution across `docs/` and root `README.md`.
- Created `docs/getting-started/README.md` as the unified getting started handbook index with foundational computing definitions (`terminal`, `container`, `environment variable`).
- Wired `test-hardening` and `check-links` into `just test` runner in `justfile`.
- Updated `dev/scripts/sh/test-panel-sabotage.sh` with explicit `APP_DEBUG=true` flag for visual diagnostic console verification.
- Updated `dev/scripts/py/screenshot_routes.py` with CSRF extraction for authenticated login and logout flows across all 13 canonical routes.
- Added `AdminController::editUser` and `AdminController::updateUser` to edit a tenant user's `first_name`, `last_name` and `role_mask`, restricted to the caller's tenant and written to `audit_logs`.
- Added `AdminController::deleteUser` behind a POST confirmation form with CSRF check, blocking self-deletion and writing a `USER_DELETE` audit row.
- Added `SuperAdminController::editOrganization` and `SuperAdminController::updateOrganization` to edit a tenant's `name` and `is_active`, written to `audit_logs`.
- Added search, sort and role filter to the admin and SuperAdmin user lists through `App\Core\ListQuery`, using bound `LIKE` parameters and whitelisted sort and role maps.
- Added `App\Core\Validator` for email, name, password, and subdomain rules, shown as field-level errors in the forms.
- Added `App\Core\Audit::record` to write `audit_logs` rows from the controllers.

- Initialized repository directory structure matching Section 2 specification in [`docs/dev/specifications/architecture.md`](dev/specifications/architecture.md).
- Created root [`README.md`](../README.md) with bidirectional navigation links to operational runbooks and specifications.
- Added agent governance rules in root [`AGENTS.md`](../AGENTS.md) enforcing non-interactive shell execution, explicit Git staging, and documentation sync.
- Defined sub-task execution contracts `task-001-scaffolding.contract.md` and `task-002-docker.contract.md` in [`docs/dev/ai/tasks.md`](dev/ai/tasks.md).
- Implemented multi-stage containerization in [`docker/php/Dockerfile`](../docker/php/Dockerfile), [`docker/nginx/nginx.conf`](../docker/nginx/nginx.conf), and [`docker-compose.yaml`](../docker-compose.yaml) for isolated local development.
- Authored air-gap image archiving utility in [`dev/scripts/sh/pre-cache.sh`](../dev/scripts/sh/pre-cache.sh) for defense against connectivity loss.
- Configured task orchestration runner in [`justfile`](../justfile) with `setup`, `build`, `up`, `down`, `test`, and `pre-cache` targets.
- Added MIT license terms in [`LICENSE`](../LICENSE), [`docs/LICENSE.md`](LICENSE.md), [`docs/licenses/MIT.md`](licenses/MIT.md), and attribution ledger in [`docs/NOTICE.md`](NOTICE.md).
- Authored dynamic YAML metadata parser in [`src/app/Views/partials/header.php`](../src/app/Views/partials/header.php) consuming [`dev/values/constants/main.yaml`](../dev/values/constants/main.yaml) for unified application branding.
- Implemented Live Panel Sabotage verification suite in [`dev/scripts/sh/test-panel-sabotage.sh`](../dev/scripts/sh/test-panel-sabotage.sh) asserting error elevation, visual diagnostic rendering, and syntax integrity.
- Expanded `SuperAdmin` authority in [`src/app/Controllers/SuperAdminController.php`](../src/app/Controllers/SuperAdminController.php) to govern cross-tenant organizations, administrators, users, and audit records under [ADR-006](dev/specifications/architecture.md).
- Implemented full canonical 13-route inventory in [`src/public/index.php`](../src/public/index.php) and [`src/app/Core/Router.php`](../src/app/Core/Router.php).
- Created pure clean-room PHP YAML dictionary parser in [`src/app/Core/Config.php`](../src/app/Core/Config.php) eliminating external dependencies.
- Authored multi-session route verification suite in [`dev/scripts/py/screenshot_routes.py`](../dev/scripts/py/screenshot_routes.py) capturing visual specimens under [`docs/specimens/screenshots/`](specimens/screenshots/).
- Created database migration and seed runner in [`dev/scripts/py/migrate.py`](../dev/scripts/py/migrate.py).
- Engineered modular XeLaTeX academic defense paper in [`Paper/`](../Paper/) (`main.tex`, `preamble.tex`, `sections/`, `references.bib`).
- Authored static asset optimization and air-gap integrity verification script in [`dev/scripts/sh/optimize-assets.sh`](../dev/scripts/sh/optimize-assets.sh).
- Authored developer runbooks in [`docs/dev/testing-runbook.md`](dev/testing-runbook.md) and [`docs/dev/tooling.md`](dev/tooling.md).
- Added AI agent operational handoff ledger in [`docs/dev/ai/agent-handoff.md`](dev/ai/agent-handoff.md).
- Added rules reconciliation audit matrix in [`docs/dev/ai/audit/rules-reconciliation.md`](dev/ai/audit/rules-reconciliation.md).
- Implemented RA 10173 data portability CSV export endpoint (`/user/export`) in [`src/app/Controllers/UserController.php`](../src/app/Controllers/UserController.php).
- Built semantic HTML views for landing (`home.php`), tenant organizations, users directory, audit logs, and dashboards.
- Added dynamic MySQL-to-SQLite3 runtime database failover and automated container chaos test suite in [`dev/scripts/sh/test-failover-chaos.sh`](../dev/scripts/sh/test-failover-chaos.sh).
- Implemented high-load chunked transactional bulk data seeder (`100,000+` records) in [`dev/scripts/py/seed_stress.py`](../dev/scripts/py/seed_stress.py).
- Implemented relational query SLA benchmark runner in [`dev/scripts/py/benchmark_queries.py`](../dev/scripts/py/benchmark_queries.py) with results audited in [`docs/dev/audit/database-performance.md`](dev/audit/database-performance.md).
- Authored accessible modular CSS variable design system in [`src/public/assets/css/base.css`](../src/public/assets/css/base.css), [`themes/default.css`](../src/public/assets/css/themes/default.css), and [`themes/lyra.css`](../src/public/assets/css/themes/lyra.css) derived from `piiiiink.json` with WCAG AA compliance.
- Created zero-dependency vanilla theme switcher engine in [`src/public/assets/css/theme-engine.js`](../src/public/assets/css/theme-engine.js).
- Authored 5-minute theme extension guide in [`docs/dev/theming-guide.md`](dev/theming-guide.md).
- Built bifurcated multi-tier onboarding runbooks for Linux (`docs/getting-started/linux/`) and Windows WSL2 (`docs/getting-started/windows/`) with student AI prompt templates.
- Upgraded automated route testing suite in [`dev/scripts/py/screenshot_routes.py`](../dev/scripts/py/screenshot_routes.py) to capture multi-viewport specimens (`desktop`, `tablet`, `mobile`) and generate [`docs/dev/audit/accessibility-report.md`](dev/audit/accessibility-report.md).

### Changed

- Updated canonical [`.gitignore`](../.gitignore) to enforce user-defined and template-level path exclusions.
- Integrated automated panel sabotage verification into [`justfile`](../justfile) `test` target.
- Purged legacy `.agents/` rules in favor of sanitized root [`AGENTS.md`](../AGENTS.md) and [`CLAUDE.md`](../CLAUDE.md).
- Expanded [`justfile`](../justfile) recipes to include `lint`, `test-sabotage`, `test-chaos`, `migrate`, `seed`, `seed-stress`, `benchmark-db`, `screenshot`, `paper`, `paper-clean`, and `optimize-assets`.
- Relocated rules reconciliation log to canonical path [`docs/dev/ai/audit/rules-reconciliation.md`](dev/ai/audit/rules-reconciliation.md).
- Relocated agent handoff journal to canonical path [`docs/dev/ai/agent-handoff.md`](dev/ai/agent-handoff.md).
- Purged 100% of inline `style="..."` attributes from all PHP views under `src/app/Views/` in favor of semantic CSS classes.

### Fixed

- Resolved PDO MySQL duplicate parameter binding exception for bitmask checks in [`src/app/Controllers/SuperAdminController.php`](../src/app/Controllers/SuperAdminController.php).
- Corrected root directory resolution in [`dev/scripts/py/migrate.py`](../dev/scripts/py/migrate.py).

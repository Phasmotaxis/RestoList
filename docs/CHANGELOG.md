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

- Added multi-tenant branding and theming engine (`TASK-022`) featuring modular CSS custom property stylesheets for Personal Platform (`src/public/assets/css/themes/lyra.css`), Filipiniana Heritage (`src/public/assets/css/themes/hiraya.css` for Hiraya Bistro), and Rustic Smokehouse (`src/public/assets/css/themes/katipunan.css` for Katipunan Grill).
- Implemented dynamic tenant theme resolution cascade in `src/app/Views/layouts/header.php` evaluating user cookie overrides, authenticated session tenant context (`tenant-hiraya-001`, `tenant-katipunan-002`), request URI patterns, and platform defaults.
- Added accessible zero-dependency live stage theme switcher in global navigation header (`src/public/assets/css/theme-engine.js` and `src/app/Views/layouts/header.php`) with dual persistence in `localStorage` and `theme` cookie (`SameSite=Lax; max-age=31536000`) enabling real-time presentation switching without page reload.
- Extended `src/public/assets/css/base.css` and `src/public/assets/css/app.css` component primitives (buttons, badges, cards, tables, forms, pagination, floor plans) to consume semantic CSS custom properties while maintaining the zero inline `style=""` invariant and WCAG AAA/AA compliance (up to 18.2:1 contrast).
- Authored comprehensive theming manual in `docs/dev/theming-guide.md` and empirical audit report in `docs/dev/ai/audit/branding-theming-report.md`.
- Recorded ADR-012 (Multi-Tenant Visual Identity, Dynamic Theming Engine, and Heritage Contrast Compliance) in `docs/dev/specifications/architecture.md` and registered theme metadata in `dev/values/constants/main.yaml`.
- Added common diagnostics and auto-escalation engine across all Justfile recipes (`dev/scripts/sh/ensure-docker.sh`) providing cross-platform Docker daemon auto-escalation, systemd socket recovery, macOS Colima/OrbStack start, Windows Docker Desktop detection, and high-contrast actionable recovery banners.
- Added `dev/scripts/sh/doctor.sh` and `just doctor` recipe providing a comprehensive 18-point environmental health audit across Docker Daemon, Docker Compose, network ports (80, 8080, 3306), host PHP runtime & PDO drivers, Python 3 / uv, Just, XeLaTeX, latexmk, biber, Zip / Unzip, and SQLite database (`src/database/eims.db`).
- Implemented `just fix-docker` and `just fix-perms` recipes for automated Docker daemon start and socket/storage permission remediation.
- Upgraded `just test` to an adaptive dual-mode runner executing `php tests/runner.php` and `php dev/scripts/php/test-hardening.php` via host PHP directly when Docker is inactive, preventing hard-fail interruptions.
- Enhanced `dev/scripts/py/migrate.py` (`just migrate` and `just seed`) with empirical MySQL availability probes and automatic SQLite fallback readiness checks (`src/database/eims.db` and `storage/fallback.sqlite`).
- Added `dev/scripts/sh/check-latex.sh` pre-flight guard in `just paper` emitting platform-specific package installation commands across apt, dnf, pacman, brew, and nix on missing XeLaTeX/latexmk binaries.
- Retrofitted all `justfile` recipes (`up`, `down`, `restart`, `logs`, `ps`, `build`, `lint`, `test-sabotage`, `test-hardening`, `test-integration`, `package`) with pre-flight diagnostics and graceful offline handling.
- Documented cross-platform diagnostics and auto-escalation patterns in `docs/dev/tooling.md` and `README.md`.
- Resolved standalone XAMPP / Apache alias rewrite compatibility by adding `src/public/.htaccess` and repository root `.htaccess`, and introducing dynamic base path detection (`Request::getBasePath()`, `Request::url()`), URI prefix stripping in `Request::getPath()`, and auto-prepending base paths on root-relative redirects in `Response::redirect()`.
- Implemented accessible, semantic role-aware navigation bar in `src/app/Views/layouts/header.php` rendering dedicated navigation links and direct logout actions per role (SuperAdmin, Restaurant Admin, FrontOfHouse, Kitchen, Customer, Staff).
- Enriched `src/app/Views/dashboards/admin.php` with direct navigation cards and action links to all 8 core ERP modules (Tables, Zones, Reservations, Waitlist, Menu 86-List, Staff RBAC, Analytics, Reviews) and Public Marketplace.
- Added missing reservation lifecycle action `noShow` in `src/app/Controllers/ReservationController.php` releasing occupied slot buckets and transitioning status to `no_show`.
- Added "Add New Menu Item" form, allowlist search/sort toolbar, and soft-delete archive action in `src/app/Views/admin/menu_86.php` and `src/app/Controllers/MenuItemController.php`.
- Added allowlist search, filter, and sort controls across all domain resource roster views (Reservations, Tables, Menu Items, Waitlist).
- Added comprehensive step-by-step XAMPP / WAMP installation instructions and prominent role credentials table to root `README.md`.
- Updated `dev/scripts/sh/package-xampp.sh` to explicitly bundle `.htaccess` dotfiles into `dist/dict24-resto-erp-xampp.zip` with verified archive integrity.
- Sanitized residual inline styles across all specimen files in `docs/specimens/screenshots/`, achieving 100% zero inline styles across the entire repository.
- Added append-only `LoyaltyLedgerService` (`src/app/Services/LoyaltyLedgerService.php`) providing atomic, immutable loyalty point accounting (`earnPoints`, `redeemPoints`, `getBalance`, `getLedger`) with dynamic `SUM(points_delta)` queries preventing balance-drift race conditions.
- Added `applyDeposit` to `DepositLedgerService` allowing held diner reservation deposits to be atomically applied toward bill settlements upon completion.
- Implemented `TableAllocator::completeReservation` for atomic reservation status transition to `completed` and immediate release of occupied 15-minute slot buckets from `reservation_slots`.
- Integrated complete end-to-end reservation checkout pipeline into `ReservationController::checkout` (ERP staff management) and `BookingFlowController::checkout` (Customer self-checkout), enforcing role authorization, deposit settlement deduction, slot bucket release, and automatic loyalty point accrual.
- Added Test 41 (`reservation_checkout_flow_deposit_and_loyalty_accrual`) in integration test runners (`tests/runner.php` and `dev/tests/runner.php`) covering full checkout lifecycles, duplicate checkout rejections, and dual-driver state verification across 41 total integration tests.
- Added `Request::setBody` to `App\Core\Request` for test and synthetic body parameter injection.
- Refreshed standalone air-gapped XAMPP deployment package (`dist/dict24-resto-erp-xampp.zip`, 176 KB) via `dev/scripts/sh/package-xampp.sh` bundling the complete source, assets, and schema seeds.
- Verified academic monograph XeLaTeX compilation (`Paper/main.pdf`, 78 KB) matching CvSU monograph requirements.
- Codified 10 master project engineering invariants derived from the ITEC50 Practical codebase directly into root `AGENTS.md` under `## Codified Project Philosophies (Adapted from ITEC50 Practical)` covering Cartesian zero-trust verification, clean-room vanilla PHP 8.3 architecture, dual-driver resilience, zero inline styles, pessimistic UI updates, 15-minute slot discretization, unified authentication, air-gapped self-sufficiency, task centralization, and red-team QA with 100% `php -l` pre-flight validation.
- Completed comprehensive Round 2 Red Team Adversarial Re-Audit in `docs/dev/ai/audit/dict24-red-team-r2-re-audit.md`, empirically validating zero broken gates, dining table mutation guards, and sort allowlist sanitization under 40+ hostile SQL injection fuzz vectors.
- Added complete Restaurant Client ERP controller and view suite for Userspace 2 per `docs/SPECIFICATION.md` Section 2.2.B and Section 4:
  - `DiningZonesController` & `DiningTablesController`: Floor plan management, semantic grid layouts, and real-time status mapping.
  - `ReservationController`: 15-minute boundary snapping, multi-table bookings, and double-booking prevention via `TableAllocator`.
  - `WaitlistController`: Walk-in party queue with automated cancellation backfilling and table reassignment.
  - `MenuItemController`: Menu catalog and real-time 86-list toggling with kitchen-specific permissions.
  - `StaffRBACController`: Multi-tenant staff directory, invite tokens, role updating, and strict privilege escalation defenses.
  - `AnalyticsController`: KPI metrics including table turnover, peak hour distribution, no-show rates, and integer centavos revenue.
  - `ReviewModerationController`: Customer review moderation queue, spam heuristics, and manager response system.
  - `RestaurantController`: Tenant restaurant provisioning and settings management.
- Added full Customer Portal routes and controllers for Userspace 3 per `docs/SPECIFICATION.md` Section 2.2.C and Section 4 (`DiscoveryController`, `SlotAvailabilityController`, `BookingFlowController`, `MenuSyncController`, `WaitlistPortalController`, `ReviewSubmissionController`, `DepositPortalController`, `LoyaltyPortalController`, `ConciergeController`, and `CustomerController`).
- Implemented real-time discretized 15-minute slot table availability calculation with abstract capacity counts and privacy-preserving status indicators without leaking floor plan table IDs or coordinates.
- Implemented real-time booking lifecycle with RFC 5322 email validation, clock drift grace checks, atomic allocation via `TableAllocator`, email confirmation verification, and self-service cancellation with slot cleanup.
- Implemented kitchen 86-list live menu sync with centavo currency formatting and dietary/allergen tag extraction.
- Implemented remote waitlist registration with live queue position calculation and cancellation.
- Implemented review submission with 1-5 rating validation, minimum length check, and anti-spam heuristic hyperlink auto-moderation.
- Implemented feature-gated deposit and loyalty ledger portals protected by `FeatureFlagService::isEnabled` guards, append-only immutable balance calculations, and overdraft protection.
- Implemented deterministic Concierge recommendation engine scoring slots by dietary matches, non-peak bonus windows, and rating.
- Implemented customer portal semantic HTML5 views (`discover.php`, `restaurant.php`, `booking.php`, `booking_confirmation.php`, `waitlist.php`) under strict Content Security Policy.
- Added dynamic route parameter parsing (`:param`) and HTTP `PUT`, `PATCH`, `DELETE` methods in native `Router.php` and `Request.php`.
- Added comprehensive integration test suite in `tests/runner.php` and `dev/tests/runner.php` covering all Client ERP and Customer Portal endpoints with 40/40 tests passing (including table mutation guard and sort allowlist sanitization).
- Added tenant-scoped restaurant ERP operations for dining zones, tables, reservation intake, and reservation administration.
- Added customer restaurant browsing, menu detail, and CSRF-protected reservation booking routes.

### Changed

- Normalized `Paper/` directory structure to lowercase `paper/`, updating `justfile` recipes (`just paper`, `just paper-clean`) and Git tracking.
- Engineered comprehensive 40-page XeLaTeX academic defense monograph (`paper/main.pdf`, 275 KB) matching CvSU DICT24 capstone standards, with 7 modular sections (8,556 words, 27 peer-reviewed and primary source citations in biblatex/biber APA style). Formalized the complete 18-table relational schema across 5 operational domains, `TableAllocator` 15-minute slot discretization, row-level pessimistic locking, formal mathematical proof by contradiction of double-booking prevention (Theorem 4.1), atomic multi-table combinability, active-state mutation guards (Invariants 5.1 & 5.2) yielding HTTP 422, signed centavo financial ledgers, and Cartesian Zero-Trust empirical evaluation across 41 integration tests, 82 hardening tests, and 15 adversarial Red Team probes.
- Repaired conflicted dashboard views while preserving strict typing, CSRF fields, and semantic CSS classes.
- Changed `APP_DEBUG` default from `true` to `false` in `.env.example` and `docker-compose.yaml` for production-safe defaults; developers must explicitly opt in to debug mode.

### Fixed

- Remediated all 9 broken findings from Red Team Adversarial Audit Round 1 (`docs/dev/ai/audit/iterative-green-r1.md`):
  - Fixed relational schema parity between `database/dict24_resto_erp.sql` and `paper/sections/06-database-design.tex` across all 22 tables with zero AST discrepancies.
  - Enforced strict topological table creation order in `dict24_resto_erp.sql` with cross-driver comment guards (`/*!40014 SET FOREIGN_KEY_CHECKS=0 */`).
  - Replaced MySQL-specific `ON DUPLICATE KEY UPDATE` syntax in `database/seeds/init_seed.sql` with cross-driver standard `REPLACE INTO`.
  - Updated `Database::bootstrapSqliteSchema()` to provision all 22 operational tables on fallback with translation of `AUTO_INCREMENT` to SQLite `AUTOINCREMENT`.
  - Synchronized temporal reservation evaluations to UTC using explicit bound parameters (`gmdate('Y-m-d H:i:s')`) in `DiningTablesController` and `TableAllocator`, eliminating the 8-hour timezone offset.
  - Fully qualified sort allowlists with explicit table aliases (`r.created_at`, `t.code`, `t.capacity`, `m.name`) across all controllers with JOIN queries.
  - Verified LaTeX bibliography citations in `paper/references.bib`, producing a clean 63-page monograph (`paper/main.pdf`, 343 KB) with zero undefined warnings.
  - Updated `dev/scripts/sh/package-xampp.sh` to bundle `README.md` alongside `README-XAMPP.txt` and verified `.htaccess` rewrite rules for subfolder deployments.
  - Populated comprehensive, parent-before-child seed records across all 22 operational tables in `database/dict24_resto_erp.sql`.
- Added dining table mutation guard in `DiningTablesController`: table deletion, archiving, and capacity reductions are strictly blocked with HTTP 422 (`Cannot archive table: active future reservations exist.`) if active future reservations (`status IN ('pending', 'confirmed', 'seated')` with `end_at > NOW()`) reference the table.
- Implemented strict `SORT_ALLOWLIST` mapping constants and direction sanitization (`ASC`/`DESC`) across primary list controllers (`ReservationController`, `DiningTablesController`, `WaitlistController`, `MenuItemController`, `ReviewModerationController`, and `RestaurantDiscoveryController`), preventing unvalidated user input from reaching `ORDER BY` clauses while maintaining safe fallbacks.
- Added `RestaurantDiscoveryController` subclass extending `DiscoveryController` to satisfy exact discovery class name contracts.
- Added `Response::getStatusCode()` and test mode state tracking in `Response.php`.
- Added `require_once __DIR__ . '/AuthMiddleware.php';` in `RoleMiddleware.php` for isolated execution resilience.
- Replaced MySQL-only feature-flag upserts and timestamp expressions in reservation, audit, stock, and deposit services with SQLite-compatible statements.
- Fixed reservation overlap detection to fetch from the prepared statement after executing it.
- Removed hardcoded test password `Password123!` from tracked specimen `docs/specimens/screenshots/09_admin_users.html` (line 74).

### Security

- Hardened `DepositLedgerService` with strict positive integer validation across all mutation methods and driver-aware atomic overdraft protection (`executeAtomicDeduction` using `BEGIN IMMEDIATE` on SQLite and `SELECT ... FOR UPDATE` on MySQL), eliminating balance inflation and unauthorized overdraft vulnerabilities.
- Hardened `LoyaltyLedgerService` with positive points validation and driver-aware atomic transaction locking in `redeemPoints()`, eliminating race conditions and double-spending vulnerabilities.
- Aligned `login_attempts` schema between SQL DDL, `LoginThrottle.php`, and LaTeX Data Dictionary (`scope`, `subject`, `attempted_at`).
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
- Engineered modular XeLaTeX academic defense paper in [`paper/`](../paper/) (`main.tex`, `preamble.tex`, `sections/`, `references.bib`).
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

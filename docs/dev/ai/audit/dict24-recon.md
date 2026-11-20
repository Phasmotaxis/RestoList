# DICT24 Final Project Reconnaissance Report

Scope: read-only inventory of the 61 Finals worktree against the DCIT 24 final project brief. No files were changed except this report. The brief PDF (DCIT-24-PROJECT.pdf) is not in the repository, so the brief requirements below come from the operator's pasted task text, not from the PDF itself.

## Executive Summary

The repository is a working multi-tenant PHP 8.3 application called EIMS, with a PDO layer, bcrypt login, role-based access, a dashboard for each role, and a CSV export. It has 5 SQL tables with primary keys, foreign keys on `users.tenant_id` and `audit_logs.tenant_id`, and seed data for every table. Every query uses PDO prepared statements with bound parameters.

The main gaps against the brief are functional. The code has INSERT and SELECT paths only. There is no UPDATE or DELETE in any PHP file, no edit or delete screen, no search, filter, or sort control in the UI, and no CRUD for the tables the brief would most likely require. There are no DATE or DECIMAL columns. No demo video, no README.txt separate from the README.md, and no exported MySQL dump of the full schema with data exist yet. The `php` binary is not installed here, so no `php -l` run was possible.

The domain is not decided. The current code models tenants, users, roles, permissions, and audit logs for a university-style administration system. That choice is an open question below.

## Repository State

- Branch: `main`. Three commits in history: `02127db`, `3214d85`, `d8c3f4b`. One author in shortlog (`whi~nyaan!`, 3 commits), so there is no visible multi-member contribution history.
- Working tree has many staged (`A`) and unstaged (`M`) changes. Full list via `git status --short`.
- Untracked: `docs/dev/ideas.md`.
- Staged deletions: `docs/audit/rules-reconciliation.md` and `docs/dev/ai-agent-handoff.md`. Their replacements are staged as `docs/dev/ai/audit/rules-reconciliation.md` and `docs/dev/ai/agent-handoff.md`. Treat the old paths as stale once the move is committed.
- Ignored but present: `.env` (matched by `.gitignore` line 245), `storage/fallback.sqlite` (matched by `.gitignore` line 8). The `storage/` directory holds a 778,240-byte SQLite fallback file.
- `.env.example` is tracked. Its keys are `APP_ENV`, `APP_DEBUG`, `APP_PORT`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_ROOT_PASSWORD`, `DB_SQLITE_PATH`.
- `Paper/main.pdf` exists (78,620 bytes, dated Nov 6). Not checked against the brief's documentation requirements.
- `dev/tmp/` is an empty directory.

## Brief Compliance Matrix

| Requirement | Status | Evidence |
|---|---|---|
| Database-driven web app in HTML, CSS, PHP, MySQL | Met | `src/app/Core/Database.php`, `src/app/Views/`, `src/public/assets/css/base.css`. MySQL 8.0 is the primary store, SQLite is the fallback. |
| At least 4 interconnected tables | Met | `src/database/migrations/001` to `004`: tenants, roles, permissions, users, audit_logs. Note: roles and permissions are not linked to users by FK (see Schema Inventory). |
| Each table has a PK | Met | All five tables declare a PK (see Schema Inventory). |
| FKs where needed | Partial | `users.tenant_id` and `audit_logs.tenant_id` reference `tenants(id)`. `audit_logs.user_id` has no FK. `users.role_mask` is a bitmask, not a FK to `roles`. |
| Appropriate types (INT, VARCHAR, TEXT, DATE, DATETIME, DECIMAL) | Partial | INT, VARCHAR, TEXT, BOOLEAN, TIMESTAMP are used. No DATE, DATETIME, or DECIMAL columns. |
| Sample records for every table | Met (seeded) | `src/database/seeds/init_seed.sql`. SQLite bootstrap in `src/app/Core/Database.php` lines 148-168 also seeds roles, permissions, tenants, users. Confirm the seeds load on the MySQL target. |
| All data read from MySQL, no hardcoded data | Partial | Dashboards and tables loop over DB results (`src/app/Views/dashboards/*.php`, `src/app/Views/superadmin/*.php`, `src/app/Views/admin/users.php`). Hardcoded items remain: default password `Password123!` at `src/app/Controllers/AdminController.php:59`; the seeded tenant and user IDs in `init_seed.sql`; the SQLite bootstrap seed in `Database.php`; the `role_mask` default of 4 in `AuthController`. The ROLE constants in `RoleMiddleware` are code constants, not data. |
| Demonstrated SELECT | Met | `AuthController.php:51`, `UserController.php:22-34`, `AdminController.php:25-38`, `SuperAdminController.php:29-129`. |
| Demonstrated INSERT | Met | `AuthController.php:67,113`, `AdminController.php:71`, `SuperAdminController.php:68,79`. |
| Demonstrated UPDATE | Missing | No `UPDATE ... SET` statement in `src/`. |
| Demonstrated DELETE | Missing | No `DELETE FROM` statement in `src/`. |
| User authentication: login | Met | `src/app/Controllers/AuthController.php:38-99`, `src/app/Views/auth/login.php`. Uses `password_verify`. |
| Invalid login handling | Met | `AuthController.php:44` (empty fields), `AuthController.php:99-103` (generic "Invalid email or password.", HTTP 401). |
| Logout | Met | `AuthController.php:105-140`, route `src/public/index.php:52-53`. Writes an audit row and destroys the session. |
| Session security | Partial | `src/app/Core/Session.php:13-14` sets `cookie_httponly` and `cookie_samesite=Lax`. No session regeneration on login was found. No CSRF token was found in any form. |
| CRUD | Partial | Create: organizations (`SuperAdminController.php:58-68`), users (`AdminController.php:52-71`). Read: all list screens. Update: missing. Delete: missing. |
| Search | Missing | No search input in any view. `grep` found no `type="search"` or `name="q"`. |
| Filter | Missing | No filter control. Only the role and tenant scoping in SQL. |
| Sort | Partial | Fixed `ORDER BY` in queries (`SuperAdminController.php:43,111,129`, `AdminController.php:38`). No user-selectable sort. |
| Dashboard or main interface | Met | `src/app/Views/dashboards/superadmin.php`, `admin.php`, `user.php`. Role-based redirects in `AuthController.php`. |
| Input validation | Partial | Required-field checks in `AdminController.php:61` and `SuperAdminController.php:61`. `trim` on input. No email format check, no password strength check, no length check, no uniqueness check before insert (the DB UNIQUE constraint catches duplicates and surfaces as a 500 path). |
| Error handling | Met | `src/app/Core/ErrorHandler.php` registers error, exception, and shutdown handlers. Login errors go to `error_log`. Views exist for 403, 404, 500. |
| Parameterized queries | Met | `src/app/Core/Database.php:38-42` sets `PDO::ATTR_EMULATE_PREPARES => false` and `ERRMODE_EXCEPTION`. `executeQuery()` at `Database.php:194-197` always prepares and binds. |
| UI and navigation | Met | Header, footer, and nav: `src/app/Views/layouts/header.php`, `footer.php`, `partials/header.php`. Responsive CSS: `src/public/assets/css/base.css`, themes in `assets/css/themes/`. |
| Complete source code | Met | 29 PHP files under `src/`, plus `src/public/assets/js/app.js` and `theme-engine.js`. |
| Exported MySQL .sql file | Partial | Only migration files and `init_seed.sql` exist. No single dump including schema and data. |
| README.txt or README.md with install and run steps | Partial | `README.md` exists and gives Docker commands (`just setup`, `just build`, `just up`). It points to `docs/GETTING-STARTED.md`. No XAMPP or non-Docker steps. |
| Test instructor credentials | Partial | Seed passwords are documented in `src/database/seeds/init_seed.sql` (comment says `Password123!`). `docs/dev/testing-runbook.md` also mentions it. No dedicated credentials section in README. |
| Documentation: title page | Missing | Not found. `Paper/main.tex` and `Paper/main.pdf` exist, content not verified against the brief. |
| Documentation: introduction, objectives, scope, system requirements | Partial | Covered in `Paper/sections/01-introduction.tex` and `docs/dev/specifications/architecture.md`. Not checked section by section. |
| Documentation: ERD | Missing | No diagram file found. The migrations describe the schema, but no ERD image or Mermaid block was found. |
| Documentation: database design | Partial | `Paper/sections/03-relational-model.tex`, `docs/dev/specifications/contracts/database.contract.md`. |
| Documentation: system features | Partial | `docs/dev/specifications/architecture.md`, `docs/dev/specifications/contracts/rbac.contract.md`. |
| Documentation: testing and results | Partial | `docs/dev/testing-runbook.md`, `docs/dev/audit/accessibility-report.md`, `docs/dev/audit/database-performance.md`, `dev/scripts/sh/test-panel-sabotage.sh`, `dev/scripts/sh/test-failover-chaos.sh`. These are operational test scripts, not a written test-results section. |
| Documentation: conclusion | Missing | Not found in the repository as a standalone section. |
| 10-15 minute demo video, every member speaks | Missing | No video file in the repository. |
| Submission deadline 2026-12-18 23:59 | Not checked | The deadline comes from the brief text. Current date is 2026-11-18, leaving 30 days. |

## Schema Inventory

Source: `src/database/migrations/001` to `004`, plus `src/app/Core/Database.php` lines 97-145 for the SQLite equivalent.

| Table | PK | FKs | Columns and types | Sample data |
|---|---|---|---|---|
| tenants | `id VARCHAR(36)` | none | `id VARCHAR(36)`, `name VARCHAR(255)`, `subdomain VARCHAR(64) UNIQUE`, `is_active BOOLEAN`, `created_at TIMESTAMP`, `updated_at TIMESTAMP` | 2 rows in `init_seed.sql` (CvSU Main Campus, CvSU College of Education). |
| roles | `id INT` | none | `id INT`, `name VARCHAR(64) UNIQUE`, `mask INT UNIQUE`, `description VARCHAR(255)` | 3 rows (SuperAdmin, Admin, User). |
| permissions | `id INT` | none | `id INT`, `name VARCHAR(64) UNIQUE`, `mask INT UNIQUE`, `description VARCHAR(255)` | 4 rows (TENANT_MANAGE, USER_MANAGE, DATA_READ, DATA_WRITE). |
| users | `id VARCHAR(36)` | `tenant_id` to `tenants(id)` ON DELETE CASCADE | `id VARCHAR(36)`, `tenant_id VARCHAR(36)`, `email VARCHAR(255) UNIQUE`, `password_hash VARCHAR(255)`, `first_name VARCHAR(128)`, `last_name VARCHAR(128)`, `role_mask INT DEFAULT 4`, `created_at TIMESTAMP` | 3 rows in seed (superadmin, admin, user). |
| audit_logs | `id VARCHAR(36)` | `tenant_id` to `tenants(id)` ON DELETE CASCADE. `user_id` has no FK. | `id VARCHAR(36)`, `tenant_id VARCHAR(36)`, `user_id VARCHAR(36)` nullable, `action VARCHAR(128)`, `entity VARCHAR(64)`, `entity_id VARCHAR(36)`, `details TEXT`, `ip_address VARCHAR(45)`, `created_at TIMESTAMP`; index on `created_at` | 3 rows in seed. |

Notes:
- `roles` and `permissions` are not referenced by any table. `users.role_mask` stores the bitmask directly. The seed and the `RoleMiddleware` constants both carry the same values, so they can drift.
- `users.role_mask` default is 4 (User). `RoleMiddleware` defines the bit values.
- The migrations use `TIMESTAMP`. The brief lists DATETIME and DATE. MySQL accepts TIMESTAMP, but a grader may check for the listed types.
- The SQLite fallback schema in `src/app/Core/Database.php` lines 97-145 is a second copy of the DDL. Changes to the MySQL migrations must be mirrored there.
- Seed SQL uses MySQL `ON DUPLICATE KEY UPDATE`. The SQLite bootstrap uses `INSERT OR IGNORE`. The two seed paths are not the same file.

## Auth, CRUD, Search, and Validation Code

- Auth: `src/app/Controllers/AuthController.php`. Login, logout, role redirect. Session in `src/app/Core/Session.php`. Route wiring in `src/public/index.php:48-53`.
- Authorization: `src/app/Middleware/RoleMiddleware.php`, `src/app/Middleware/AuthMiddleware.php`. Used by each controller's dashboard and action methods.
- CRUD: `src/app/Controllers/SuperAdminController.php` (organizations create, users list, audit list), `src/app/Controllers/AdminController.php` (users list, user create), `src/app/Controllers/UserController.php` (dashboard, CSV export).
- Search, filter, sort: no user-facing search or filter. Sort is fixed in SQL.
- Validation: `trim` plus required checks in the two POST handlers. No format, length, or uniqueness checks before insert.
- Prepared statements: all runtime queries go through `Database::executeQuery()`, which calls `prepare()` and `execute($params)`. No string interpolation of user input into SQL was found in controllers. Two uses of string interpolation exist in `dev/scripts/py/seed_stress.py` and `dev/scripts/py/benchmark_queries.py`, which are dev tools, not runtime code.
- Hardcoded values: default password `Password123!` at `src/app/Controllers/AdminController.php:59`. Default role 4 in `AdminController.php:60`. The CSV export header text at `src/app/Controllers/UserController.php` cites RA 10173.
- Routes (`src/public/index.php`): `/`, `/login` (GET, POST), `/logout` (GET, POST), `/superadmin/dashboard`, `/superadmin/organizations` (GET, POST), `/superadmin/users`, `/superadmin/audit-logs`, `/admin/dashboard`, `/admin/users` (GET, POST), `/user/dashboard`, `/user/export`, `/health`, `/diagnostics/sabotage`.

## README and Test Credentials

- `README.md` exists. It describes the system as EIMS and gives a Docker quick run (`cp .env.example .env`, `just setup`, `just build`, `just up`, `just test`). It links to `docs/GETTING-STARTED.md`. It has no XAMPP instructions and no credential section.
- Test credentials are in `src/database/seeds/init_seed.sql` (`superadmin@cvsu.edu.ph`, `admin@cvsu.edu.ph`, `user@cvsu.edu.ph`, all with password `Password123!`). The same password appears in `docs/dev/testing-runbook.md` and `src/app/Views/admin/users.php`.
- No `.sql` export of the full database exists. Only per-table migrations and a seed.

## PHP Lint

Not run. `php` is not installed on this host (`command -v php` returned nothing). The 29 PHP files to lint are all under `src/`:
`src/app/Controllers/` (4 files), `src/app/Core/` (7), `src/app/Middleware/` (2), `src/app/Views/` (15 `.php` files including layouts and partials), `src/public/index.php`. No PHP files exist outside `src/`. All 29 are tracked by git.

## Gap List by Rubric Weight

**Database (25)**
1. No UPDATE statement anywhere in the code. Add an edit path for at least one table, prepared with bound parameters.
2. No DELETE statement anywhere in the code. Add a delete path for at least one table, with an FK-aware order.
3. No DATE or DECIMAL columns. The brief lists them as expected types. Decide where they fit (the operator decides the domain).
4. Roles and permissions are not linked to users by FK. `users.role_mask` is a bitmask, not a reference. Decide whether a link table or FK is required.
5. `audit_logs.user_id` has no FK to `users`. Add one or document the reason.
6. Single full `.sql` export (schema plus data) is missing. Only per-migration files and a seed exist.
7. DDL is duplicated in `Database.php` for SQLite. Keep MySQL and SQLite schema in step.
8. ERD is missing.

**Functionality and CRUD (30)**
1. No search control on any list screen.
2. No filter control on any list screen.
3. Sort is fixed. No user-selectable sort.
4. No edit or delete for users or organizations.
5. No CSRF token on POST forms (`/login`, `/logout`, `/superadmin/organizations`, `/admin/users`).
6. No session regeneration on login (`AuthController::login`).
7. Input validation is limited to required fields. Add email format, length, and password rules.
8. Duplicate email or subdomain submits reach the DB UNIQUE constraint and fail with a server error path rather than a user message. Confirm the message shown.
9. Hardcoded default password `Password123!` in `AdminController.php:59`.

**UI (15)**
1. No search, filter, or sort controls in the views.
2. No edit or delete buttons.
3. UI for the 4 (or more) tables beyond users and tenants is not evident. Audit logs and roles are displayed read-only; permissions are not displayed.
4. Mobile layout and the 16px gutter are specified in the brief context only through the design docs. Not verified in a browser in this run.

**Source Code and Docs (10)**
1. `php -l` not run (PHP not installed).
2. Stale staged deletions at `docs/audit/rules-reconciliation.md` and `docs/dev/ai-agent-handoff.md`; the replacements are staged at new paths. Commit or revert the move deliberately.
3. Untracked `docs/dev/ideas.md`.
4. README is Docker-only. No XAMPP path. No credentials section.
5. Title page, introduction, objectives, scope, system requirements, conclusion: not confirmed in `Paper/`. Check `Paper/main.pdf` against the brief outline.
6. Testing and results section: test scripts exist, a written results section does not.

**Video (10)**
1. No demo video in the repository. Not started as far as the repository shows.

**Individual participation (10)**
1. Git history has one author with 3 commits. The repository alone cannot show each member's contribution. Operator must confirm group roles and split of work.

## Open Questions for the Operator

1. Which real-world problem does the system address? The current code models a multi-tenant university administration system (EIMS), but the brief's domain choice is not recorded in the repository.
2. Should MySQL run via Docker (the current `docker-compose.yaml`) or XAMPP (the usual setup for a Windows demo)? The README covers only Docker.
3. Which tables should carry UPDATE and DELETE demos, and should roles and permissions become referenced tables instead of constants?
4. Should DATE and DECIMAL columns be added, and to which tables? The brief names those types but the current domain has no monetary or calendar-date fields.
5. Should the test instructor credentials stay the seeded `Password123!` accounts, or be changed for submission?
6. Is the SQLite fallback part of the submission, or is MySQL the only required store?
7. Who are the group members, and which parts of the video and the documentation does each one own?
8. Is the existing `Paper/main.pdf` the documentation deliverable, or should a new document be written for the brief outline?

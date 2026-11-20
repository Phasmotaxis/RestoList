# DICT24 GREEN Round 1: Implementation Report

Scope: domain-neutral hardening and user/tenant management of the existing EIMS. No new domain entities, no DATE/DECIMAL columns.

Verification environment: PHP is not installed on the host, so lint and runtime checks ran in the `php:8.3-cli` Docker image. The runtime smoke test used the SQLite fallback. MySQL was not exercised.

## Item 1: Lint baseline

- Files changed: none for lint itself.
- Command (baseline, run before edits): `docker run --rm -v "$PWD":/app -w /app php:8.3-cli sh -c 'for f in $(find src -name "*.php" | sort); do php -l "$f" || echo "FAIL $f"; done'`
- Result: 29 files, no syntax errors.
- Command (after edits): `docker run --rm -v "$PWD":/app -w /app php:8.3-cli sh -c 'n=0; for f in $(find src -name "*.php" | sort); do php -l "$f" >/dev/null 2>&1 || { echo "FAIL $f"; n=$((n+1)); }; done; echo "php-files-failed=$n"; find src -name "*.php" | wc -l'`
- Result: `php-files-failed=0`, 34 PHP files under `src/` (29 baseline plus `Core/Audit.php`, `Core/ListQuery.php`, `Core/Validator.php`, `Views/admin/user_edit.php`, `Views/superadmin/organization_edit.php`).
- Inline `style=""` check: `grep -rn 'style="' src/app/Views | wc -l` returns 0.

## Item 2: Hardcoded default password removed

- Files changed: `src/app/Controllers/AdminController.php`, `src/app/Views/admin/users.php`, `src/app/Core/Validator.php`.
- Verify: `grep -rn "Password123" src/app | wc -l` returns 0. Seed data in `src/database/seeds/init_seed.sql` and the migration runner still carry the seeded demo hash; that is outside item 2 and is not changed.
- Runtime: POST `/admin/users` with `password=short` returned HTTP 422 with `Password must be at least 12 characters.` A valid 15-character password created the user (HTTP 302).
- Result: passed. Passwords are capped at 72 bytes because bcrypt ignores bytes past 72.

## Item 3: CSRF

- Files changed: `src/app/Core/Session.php` (`csrfToken`, `csrfField`, `verifyCsrf`), `src/app/Controllers/AuthController.php`, `src/app/Views/auth/login.php`, `src/app/Views/dashboards/admin.php`, `src/app/Views/dashboards/superadmin.php`, `src/app/Views/dashboards/user.php`, `src/app/Views/superadmin/organizations.php`, `src/app/Views/admin/users.php`, `src/app/Views/admin/user_edit.php`, `src/app/Views/superadmin/organization_edit.php`.
- Every POST handler calls `Session::verifyCsrf()`: `login`, `logout` (POST only), `createUser`, `updateUser`, `deleteUser`, `createOrganization`, `updateOrganization`.
- Runtime: POST `/login` with `_csrf=bogus` returned 403. With a valid admin session, POST `/admin/users` without `_csrf` returned 403.
- Not verified: POST `/admin/users/delete` and `/admin/users/update` without a valid token were not exercised with an authenticated session. An earlier attempt got a 302 only because login had failed, so it proved nothing about CSRF.
- Result: login and create paths passed. Delete and update CSRF rejection is implemented but unverified at runtime.
- Limitation: `GET /logout` remains routed (existing behavior). It is not CSRF-protected and only ends the session. Only POST `/logout` verifies the token.

## Item 4: Session fixation

- Files changed: `src/app/Core/Session.php` (`regenerate`), `src/app/Controllers/AuthController.php`.
- Login calls `Session::regenerate()` (which calls `session_regenerate_id(true)`) before setting `user_id`.
- Runtime: login with a valid token returned 302 to the role dashboard. The session ID rotation was not compared before and after login in a separate test.
- Result: implemented and verified by code path; the before/after cookie comparison is not separately recorded.

## Item 5: UPDATE path

- Files changed: `src/app/Controllers/AdminController.php` (`editUser`, `updateUser`, `findManageableUser`, `roleError`), `src/app/Controllers/SuperAdminController.php` (`editOrganization`, `updateOrganization`, `findTenant`), `src/app/Views/admin/user_edit.php`, `src/app/Views/superadmin/organization_edit.php`, `src/app/Views/admin/users.php` (Edit links), `src/app/Views/superadmin/organizations.php` (Edit links), `src/public/index.php` (routes), `src/app/Core/Audit.php`.
- Users: `first_name`, `last_name`, `role_mask`. Prepared statements. Tenant scope enforced in `findManageableUser` (`WHERE id = :id AND tenant_id = :tenant_id`). SuperAdmin-role accounts are not manageable by admins. Admins cannot change their own role.
- Tenants: `name`, `is_active`. `SuperAdminController` requires `ROLE_SUPERADMIN`. The subdomain is shown read-only.
- Audit rows: one `USER_UPDATE` per changed field, one `TENANT_UPDATE` per changed field.
- Runtime: admin edit of another tenant user returned 302. Admin edit of own role (4 instead of 2) returned 422 with the self-role error. Admin POST to `/superadmin/organizations/update` returned 403, but that came from the role check in `RoleMiddleware`, before CSRF runs, so it does not prove the tenant-edit gate on its own (it does show the role gate works). Audit rows observed: `USER_UPDATE | First name: Ana -> Anna`, `USER_UPDATE | Role mask: 4 -> 2`, `TENANT_UPDATE | Name: CvSU Silang -> CvSU Silang Campus`, `TENANT_UPDATE | Active: yes -> no`.
- Cross-tenant edit: the GET and POST paths use `findManageableUser`, which filters by `tenant_id`. Not separately tested with a second admin in a second tenant. Result: implemented, partially verified.

## Item 6: DELETE path

- Files changed: `src/app/Controllers/AdminController.php` (`deleteUser`), `src/app/Views/admin/user_edit.php` (confirmation form), `src/public/index.php`.
- POST confirmation: requires `confirm=1` and a valid CSRF token. Self-deletion is blocked with a form error.
- Audit row: `USER_DELETE`.
- Tenant deletion: NOT implemented on purpose. Reason: a tenant owns its users and audit history through `ON DELETE CASCADE` foreign keys, so deleting a tenant erases audit records. Deletion also changes data retention for every member of the tenant, which is a decision for the operator.
- Runtime: self-delete returned 422 with `You cannot delete your own account.` Delete without confirmation returned 422. Confirmed delete returned 302, and the row was removed. Audit row observed: `USER_DELETE | Admin deleted a tenant user`.
- Result: passed for user delete. Tenant delete: not done, reason above.

## Item 7: Search, sort, filter

- Files changed: `src/app/Core/ListQuery.php` (new), `src/app/Controllers/AdminController.php` (`renderUsers`), `src/app/Controllers/SuperAdminController.php` (`users`), `src/app/Views/admin/users.php`, `src/app/Views/superadmin/users.php`.
- `q` is bound through three named placeholders (`:q_email`, `:q_first`, `:q_last`) with `ESCAPE '!'`. `%` and `_` in input are escaped so they match literally.
- Sort: whitelist map of keys to SQL column lists. Unknown keys fall back to the first entry. Direction accepts only `ASC` or `DESC`. The ORDER BY fragment is built only from the whitelist.
- Role filter: whitelist of role masks. Unknown values are ignored.
- Runtime: `q=ana` returned 1 matching row. `q=%` returned 0 matches (wildcard escaped). `sort=email;DROP&dir=x'` returned 200 with default sort. `role=admin&sort=role&dir=DESC` returned 200. SuperAdmin `q=campus&role=admin&sort=tenant&dir=DESC` returned 1 match.
- Result: passed.

## Item 8: Validation

- Files changed: `src/app/Core/Validator.php` (new), both controllers, all user and organization forms.
- Rules: email uses `filter_var(FILTER_VALIDATE_EMAIL)` with a 255-character cap. Names are required and capped at 128 characters (255 for organization names). Password is at least 12 characters, at most 72 bytes. Subdomain matches `^[a-z0-9](?:[a-z0-9-]{0,62}[a-z0-9])?$`.
- Errors render per field next to the input. Validation failures return 422, not 500.
- Runtime: bad email and short password on create returned 422 with field messages. Invalid subdomain `Bad Slug!` returned 422 with a field message.
- Result: passed.

## Item 9: UNIQUE violations

- Files changed: `src/app/Core/Validator.php` (`isUniqueViolation`), `AdminController::createUser`, `SuperAdminController::createOrganization`.
- Detection: MySQL errno 1062 (`errorInfo[1]`), or SQLSTATE 23000 whose message contains `unique`. The SQLite message `UNIQUE constraint failed` matches the second rule. Other 23000 errors (foreign key, NOT NULL) are re-thrown.
- Runtime (SQLite): duplicate email returned 422 `This email address is already registered.` Duplicate subdomain returned 422 `This subdomain is already in use.` Neither response contained SQL text.
- MySQL: not run. The code path matches errno 1062 but has not been exercised against MySQL.
- Result: passed on SQLite; MySQL unverified.

## Deliverables

- `docs/CHANGELOG.md`: entries under `## [Unreleased]`, with `### Security` and additions to `### Added`.
- This report: `docs/dev/ai/audit/dict24-green-r1.md`.
- `docs/dev/ai/audit/dict24-recon.md` was not modified.
- Not committed or staged. The worktree is left for operator review.

## Known gaps

- MySQL path not exercised at runtime.
- Cross-tenant edit denial not tested with two admin tenants.
- Session ID rotation not captured before and after login.
- Tenant delete intentionally not implemented (see item 6).
- `GET /logout` remains.
- The Docker test container `eims-green-r1` was used for runtime checks and has been removed.

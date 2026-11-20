# DICT24 RED Round 1: Adversarial Review of GREEN R1

Scope: attack the uncommitted GREEN R1 diff (24 files, `src/`). No files under `src/`, `dev/`, `docs/CHANGELOG.md` or `dict24-green-r1.md` were edited.

## Environment

- PHP is not on the host. All PHP ran in `php:8.3-cli` containers. The stock image lacks `pdo_mysql`, so I added it in the test container with `docker-php-ext-install pdo_mysql`. Without it, the app silently falls back to SQLite, which would invalidate every MySQL result.
- MySQL: throwaway `mysql:8.0` container `dict24-red-mysql` on network `dict24-red-net`. Migrations ran from `src/database/migrations` through `/docker-entrypoint-initdb.d`. Seed loaded with `docker exec -i dict24-red-mysql mysql -uroot -predroot eims_db < init_seed.sql`.
- App: copy of `src/` at `/tmp/dict24-red/app`, served by `php -S 0.0.0.0:8000 -t public public/index.php` in container `dict24-red-app` (`APP_DEBUG=true`). A second container, `dict24-red-app-nodebug`, ran with `APP_DEBUG=false`.
- Health check confirmed `driver: mysql`, `fallback_active: false`.
- HTTP probes used `curlimages/curl` on the same Docker network, with cookie jars in `/tmp/dict24-red/jars/`.
- Evidence: `/tmp/dict24-red/evidence/`.
- The running `eims_*` stack was not touched.

Lint was re-run in `php:8.3-cli`: `docker run --rm -v "$PWD/src":/app -w /app php:8.3-cli sh -c 'for f in $(find . -name "*.php"); do php -l "$f"; done'` gave `files=34 failed=0`. Evidence: `lint.txt`.

## Results

| # | Attack | Exact command (summary) | Observed result | Verdict | Evidence |
|---|---|---|---|---|---|
| 1 | MySQL CRUD, search, duplicate email and subdomain | `POST /admin/users` (create), `POST /admin/users/update`, `GET /admin/users?format=json&q=…`, `POST /admin/users/delete` against `dict24-red-app` on MySQL. Dup email via create with `admin@cvsu.edu.ph`. Dup subdomain via `POST /superadmin/organizations` with `subdomain=cvsu-main`. Raw PDO error from `Database::executeQuery`. | Create 302, edit 302 and row updated to `Anna`, search `Anna` 1 row, `%` 0 rows, delete 302 and row gone. Dup email 422 `This email address is already registered.` Dup subdomain 422 `This subdomain is already in use.` Raw PDO error is `errno=1062 … Duplicate entry 'admin@cvsu.edu.ph' for key 'users.email'`, which is never shown to the browser (0 matches for SQLSTATE, Duplicate entry, 1062 in the response). | HELD | `crud-status.txt`, `a1-status.txt`, `a1-subdomain-status.txt`, `a1-raw-mysql.txt` |
| 2 | CSRF on update and delete | `POST /admin/users/update` and `/admin/users/delete` with (a) no `_csrf`, (b) `_csrf=deadbeef`, (c) token from second admin session. Positive control: valid token on update. | (a), (b), (c) all 403 for both routes. DB row unchanged (`Standard`, role 4). Positive control with valid token: 302, row changed to `RedProbe`, so the 403s are real rejections and not a broken form. | HELD | `csrf-status.txt`, `positive-control.txt`, `csrf-db-state.txt` |
| 3 | Cross-tenant edit or delete; role escalation through edit and create | Admin in `cvsu-main` GET/POST/delete `usr-tenantB-999` (tenant `cvsu-educ-002`). POST update `role_mask=1`, `role_mask=3`, and edit `usr-superadmin-001`. Create with `role_mask=1` and `3`. | Cross-tenant GET, update and delete all 404, row unchanged. `role_mask=1` and `3` on update: 422. Edit SuperAdmin account: 404. Create with `role_mask=1` or `3`: 422. Create with `role_mask=2` (Admin) succeeded (302), which is expected because Admin is assignable. No escalation found. | HELD | `a3-status.txt`, `a3b-status.txt`, `a3-create-escalation.txt` |
| 4 | Admin reaches tenant edit route | `POST /superadmin/organizations/update` and `GET /superadmin/organizations/edit` as Admin, with a valid token. | Both 403. Tenant names unchanged. The 403 comes from `RoleMiddleware`, which runs before CSRF, so this proves the role gate and not the CSRF path. | HELD | `a4-status.txt` |
| 5 | Self-delete bypass | `POST /admin/users/delete` with `id=usr-admin-002` (own id), `confirm=1`, valid token. | 422 `You cannot delete your own account.` Row still exists. The check compares the POSTed id against the session `user_id`, so changing the form does not get around it. Changing own role is also blocked (roleError). | HELD | `a5-status.txt` |
| 6 | Search, sort and filter injection | `GET /admin/users?format=json&q=` with `%`, `_`, `a'b`, `' OR '1'='1`, `x%25`. Then `sort=email;DROP TABLE users&dir=x';--`, `role=1`, `sort=email&dir=DESC`. | `q=%` 0 rows, `q=_` 0 rows, so wildcards are escaped. `q=a` 3 rows, which is correct. Quote and SQL fragments return 0 rows with no error. Bad sort and dir fall back to the default sort (first row `admin@…`). `sort=email&dir=DESC` gives `user@…` first, so the sort direction works. The `users` table still has 5 rows after the injection attempts. | HELD | `a6-counts.txt`, `a6-status.txt` |
| 7 | Session regeneration and logout | Read the `PHPSESSID` cookie before `POST /login` and after. Then `POST /logout` with a valid token and replay the old cookie against `/admin/dashboard` and `/admin/users`. | Cookie changed: `a0bc7b…` before, `824ac9…` after login. Logout 302. Replaying the pre-logout authenticated cookie: 302 to login on both routes, so the session is dead. | HELD | `a7-status.txt`, `a7-logout-v2.txt`, `s7*` cookie notes |
| 8 | Password boundary on create | `POST /admin/users` with an 11-character and a 12-character password, valid token. | 11 chars: 422. 12 chars: 302, row `bound12@x.test` created in MySQL. The 72-byte upper bound was not tested, so that part is UNVERIFIED. | HELD (72-byte cap UNVERIFIED) | `a8-status.txt` |
| 9 | Error leakage on forced 500 | `GET /diagnostics/sabotage` on `dict24-red-app` (`APP_DEBUG=true`) and on `dict24-red-app-nodebug` (`APP_DEBUG=false`). | Both return 500. The `APP_DEBUG=false` response still contains the `Call Stack Trace` block, `#0 [internal function]: {closure}…`, the absolute file path `/var/www/html/public/index.php:106`, and a source excerpt. `ErrorHandler::renderDiagnosticScreen` receives `getTraceAsString()` with no check on `APP_DEBUG`. The handler itself does work (it renders the panel), so the "ErrorHandler still works" claim holds; the "no stack trace shown" claim does not. | BROKEN (see B2) | `a9-status.txt`, `a9-nodebug-matches.txt`, `a9-gating.txt` |
| 10 | Hygiene: `style="`, `Password123`, default password on fresh install | `grep -rn 'style="' src/app`, `grep -rn Password123 src`, then `password_verify("Password123!", <hash in Database.php:163>)` in `php:8.3-cli`. Fresh SQLite fallback bootstrap with `DB_CONNECTION=sqlite` and an empty file. Re-run `init_seed.sql` after changing the admin password. | `style="` in `src/app`: 0. `Password123` appears in `src/database/seeds/init_seed.sql` (comment) and the hash is in `init_seed.sql` and `Database.php:163`. The hash verifies `Password123!`. A fresh SQLite fallback creates `superadmin@cvsu.edu.ph` (role 1), `admin@…` (role 2) and `user@…` (role 4), all with that password (`default-pw-valid`). Re-running the seed reset `admin@cvsu.edu.ph` from `NewStrongPass!234` back to `Password123!` (login with the old password 302, with the new one 401). | BROKEN (see B1, B3) | `hyg-style.txt`, `hyg-password.txt`, `fallback-fresh-users.txt`, `reseed-test.txt` |
| 11 | Docs truth | Compared `docs/CHANGELOG.md` `[Unreleased]` and `dict24-green-r1.md` against the diff and against the runtime results above. | CHANGELOG security and added entries match the code (the CSRF, session, validator, ListQuery, Audit and edit/delete items all exist). GREEN R1 is honest about its gaps: MySQL not exercised, cross-tenant untested, session rotation uncaptured, `GET /logout`, and seed data unchanged. Its "Session ID rotation not captured" gap is now closed by attack 7. The `Added` list in CHANGELOG contains an unrelated group of R0 entries after a blank line. That is formatting only. Nothing is claimed that the diff fails to support, but the report does not mention that the seed still ships a live default password for a fresh install, which is the gap in B1. | HELD (with a gap note) | `changelog-unreleased.txt` |

## Additional observation

- `GET /logout` (routed in `public/index.php`) ends the session with no token. Logout CSRF is therefore possible: a page can load `/logout` in an `<img>` tag. Green disclosed this. Verdict: BROKEN, low severity, known gap.

## BROKEN items, ranked by severity

**B1. Fresh-install SQLite fallback creates a SuperAdmin with a public default password (high).** `src/app/Core/Database.php:163` seeds `superadmin@cvsu.edu.ph` (role 1) with the hash for `Password123!`. The hash verifies, and the account is created on any fresh fallback database. `init_seed.sql` carries the same hash for MySQL. Compose does not mount the seed into `docker-entrypoint-initdb.d`, so the MySQL path is less exposed, but the seed file ships the same password and is runnable by hand. Green's "no leftover default password on fresh-install code paths" is not true. Condition: the fallback is active, which the project advertises as its offline mode. Evidence: `fallback-fresh-users.txt`.

**B2. Stack trace and file paths shown to the browser with `APP_DEBUG=false` (medium-high).** `ErrorHandler::renderDiagnosticScreen` always renders `getTraceAsString()` and the source excerpt. This affects every 500 path, not only the sabotage route. Evidence: `a9-nodebug-matches.txt`.

**B3. Re-running `init_seed.sql` overwrites changed passwords (medium).** The seed's `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)` resets every seeded account to `Password123!` on each run. An operator who rotates the admin password and later re-seeds re-opens the default. Evidence: `reseed-test.txt`.

**B4. `GET /logout` can be triggered cross-site (low).** Disclosed by green. Evidence: `get-logout.txt`.

## Concessions and caveats

- Attack 3 cross-tenant results came from a manually inserted tenant-B user, not from a second admin login. Attack 4's 403 comes from the role gate before CSRF.
- The 72-byte password cap was not tested.
- The SQL-injection string tests show no SQL error and no data change. That shows the parameter binding holds for these inputs, not for every input.

## What green must fix before round 2

Green must stop the fresh-install fallback from creating accounts with `Password123!`. The fix is to generate or require a first-run password (or a setup step) in `Database::bootstrapSqliteSchema`, and to strip the default hash and the comment from `init_seed.sql`, or make the seed insert-only with no `password_hash` update. Green must also gate `renderDiagnosticScreen` trace and source output on `APP_DEBUG` (or an explicit env flag), and show a generic 500 page otherwise, with the full trace written to the error log. Green should correct the audit doc and CHANGELOG to say that the default password is still shipped, and to list `GET /logout` as an open issue. Green should also capture the 72-byte boundary test and run the cross-tenant case with a real second admin session.

## Cleanup

Containers `dict24-red-app`, `dict24-red-app-nodebug` and `dict24-red-mysql` and network `dict24-red-net` are removed after this report was written. Evidence stays in `/tmp/dict24-red/evidence/`.

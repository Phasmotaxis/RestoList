# DICT24 GREEN Round 2: Fixes for RED R1

Scope: the BROKEN items in `dict24-red-r1.md` (B1 to B4), plus the injection and hardening specs in the brief. Red's reports were not edited.

## Environment and commands

- PHP: `docker run --rm -v "$PWD/src":/app -w /app php:8.3-cli ...` (stock image; `pdo_sqlite` is built in. `pdo_mysql` was not exercised in this round).
- Unit checks: `docker run --rm -v "$PWD/src":/app -v "$PWD/dev":/dev-scripts:ro -e DB_CONNECTION=sqlite -e DB_SQLITE_PATH=/tmp/hardening.sqlite -w /app php:8.3-cli php /dev-scripts/scripts/php/test-hardening.php`
- HTTP probes: app container on `127.0.0.1:18765`, `php -S` over `src/public`, SQLite store at `/tmp/dict24-green-r2/storage`, `APP_DEBUG=false`. A second container on `18766` ran with `APP_DEBUG=true`.
- Lint: `php -l` over all 36 `src/` PHP files in `php:8.3-cli`: `files=36 failed=0`.
- Not run: MySQL 8.0 (no MySQL container in this round), HTTPS, a real browser for CSP.

## Results

| # | Item | Files changed | Exact verification | Observed result | Status |
|---|---|---|---|---|---|
| B1 | Fresh SQLite fallback creates SuperAdmin with `Password123!` | `src/app/Core/Database.php`, `src/database/bootstrap_superadmin.php` (new), `src/database/seeds/init_seed.sql` | `docker run ... php database/bootstrap_superadmin.php` with `SUPERADMIN_EMAIL`/`SUPERADMIN_PASSWORD`, then `grep -rn "Password123" src` | Fresh store: `SuperAdmin created.` (exit 0). Re-run: `Password unchanged` (exit 0). 5-char password: exit 2. `grep` on `src/`: no matches. | DONE |
| B2 | Stack trace and file paths shown with `APP_DEBUG=false` | `src/app/Core/ErrorHandler.php`, `src/app/Views/errors/500.php`, `src/public/assets/css/diagnostic.css` (new) | `curl http://127.0.0.1:18765/diagnostics/sabotage` (prod) and `:18766` (debug), count `Call Stack Trace` and `#0` | Prod: 0 trace markers, body shows request ID `d819118d12c9`. Debug: `Call Stack Trace` present (1). Server log holds `request_id=` lines with full detail (10 lines). | DONE |
| B3 | Re-running `init_seed.sql` overwrites `password_hash` | `src/database/seeds/init_seed.sql` | Seed no longer contains any `INSERT INTO users`, so no `ON DUPLICATE KEY UPDATE password_hash`. `bootstrap_superadmin.php` re-run leaves hash unchanged. | Behavior verified on SQLite only. MySQL re-seed not run. | PARTIAL (MySQL not run) |
| B4 | `GET /logout` cross-site logout | `src/public/index.php` | `curl http://127.0.0.1:18765/logout` | 404. Logout is `POST` with `_csrf` only. | DONE |
| SQLi | Bound parameters, whitelists | none (existing code verified) | `test-hardening.php` payloads: `' OR '1'='1`, `'; DROP TABLE users; --`, `UNION SELECT`, `%`, `_`; HTTP `GET /admin/users?q=...&format=json` | 0 rows for each payload, `users` table intact (1 row), `sort=email;DROP TABLE users&dir=x';--` falls back to default (HTTP 200). Unit: 22 of 22 PASS. | DONE |
| PHP injection | No eval/assert/create_function/unserialize or variable include | none | `grep -rnE "\beval\s*\(\|\bassert\s*\(\|create_function\|unserialize\s*\(" src/app src/public` and include-with-variable grep | No matches. | DONE |
| Path traversal | Input paths rejected | none | `curl "/user/export?file=../../etc/passwd"` as plain user | 200, CSV for the logged-in user only. `file` is never read; no `root:` line in output. Export has no path input, so no failing case exists to test. | DONE |
| Command injection | No shell with user input | none | `grep -rnE "shell_exec\|passthru\|\bexec\s*\(\|\bsystem\s*\(\|proc_open\|popen\s*\(" src/app src/public src/database` and backtick grep | Only `PDO::exec` SQL matches (not shell). No backtick or shell call. | DONE |
| XSS | `htmlspecialchars` ENT_QUOTES on echo | none (existing) | HTTP: create user with `<script>` in `first_name` and `"><img onerror>` in `last_name`; create org with `"><script>alert(3)</script>` name; read `/admin/users` and `/superadmin/organizations` | Stored and rendered escaped (`&lt;script&gt;`, `&quot;&gt;&lt;img`). Raw `<script>alert(` count 0. | DONE |
| Mass assignment | Whitelisted fields only | none (existing) | HTTP: create user with `role_mask=1` and `is_active=1`; org update with `role_mask=1` and `subdomain=hijack` | Create with `role_mask=1` returns 422. `is_active` is not read by create. Org update ignored `subdomain` and `role_mask`; row kept `subdomain=cvsu-main`. | DONE |
| Open redirect | Fixed internal targets | none (existing) | `curl "/login?next=https://evil.example"`; login POST with the same query | GET: no `Location`. POST success: `Location: /superadmin/dashboard`. `next` is never read. | DONE |
| Cookie flags | SameSite Strict, HttpOnly, Secure on HTTPS | `src/app/Core/Session.php` | `curl -D - -o /dev/null http://127.0.0.1:18765/login`, grep `Set-Cookie` | `HttpOnly; SameSite=Strict`. `Secure` is set only when `Session::isHttps()` is true; the HTTPS path was not tested. | PARTIAL (HTTPS not tested) |
| CSP / headers | No inline script or style | `src/public/index.php`, `src/public/assets/css/diagnostic.css`, `src/app/Core/ErrorHandler.php` | `curl -sI http://127.0.0.1:18765/login`; `grep -rnE 'style="\|<style\|style=' src` | CSP header present with `script-src 'self'` and no `unsafe-inline`. `X-Frame-Options: DENY`, `nosniff`. grep: no inline style. Browser console check not run. | DONE |
| Brute force | 5 failures per IP or email in 15 min | `src/app/Core/LoginThrottle.php` (new), `src/app/Controllers/AuthController.php`, `src/public/index.php` | Loop of 6 `POST /login` with the same email and wrong passwords | Attempts 1 to 5: 401. Attempt 6: 429. Correct password while locked: 429. Unit: 4 failures allowed, 5th blocks, per-email block works across IPs. | DONE |
| Password policy | At least 12 chars, bcrypt | none (existing `Validator::password`) | `test-hardening.php`; `password_hash(..., PASSWORD_BCRYPT)` in create and bootstrap | 11 chars rejected, 12 accepted, 73 bytes rejected. | DONE |
| Enumeration | Same message and similar timing | `src/app/Controllers/AuthController.php` | HTTP login, 5 unknown emails and 5 wrong passwords, `curl -w %{time_total}`; throttle cleared between requests | Message is `Invalid email or password.` for both. Unknown email mean 47 ms (range 46 to 47 ms). Wrong password mean 47 ms (range 47 to 49 ms). The first run measured 93 ms vs 48 ms because the dummy hash was built per request. Fixed by a constant hash. | DONE |
| Changelog | Entries under `[Unreleased]` | `docs/CHANGELOG.md` | `grep -n "Unreleased" docs/CHANGELOG.md` | 11 past-tense entries added to `### Security`. | DONE |
| Seed doc | Runbook lists `Password123!` as valid | `docs/dev/testing-runbook.md` | `grep -n Password123 docs/dev/testing-runbook.md` | Table replaced with bootstrap instructions. | DONE |
| Dev tooling | `dev/scripts/py/seed_stress.py`, `dev/scripts/py/screenshot_routes.py`, `docs/specimens/*` still use `Password123!` | none | `grep -rn "Password123" dev docs` | Not changed. These are outside `src/`. | NOT DONE (outside `src/`; needs operator decision on the specimens) |

## Caveats

- Lint and unit checks ran in the stock `php:8.3-cli` image. MySQL 8.0 was not started, so the MySQL path of `LoginThrottle::ensureTable` and the re-seed behavior are unverified.
- `Secure` on the cookie depends on `HTTPS` or port 443. No HTTPS test was run.
- Brute-force and throttle state live in the `login_attempts` table. The table is created on first use, because `bootstrapSqliteSchema` returns early on existing SQLite files.
- Nothing was committed. The Round 2 changes sit in the working tree with the Round 1 diff.

## Summary

- DONE: 16 rows (B1, B2, B4, SQLi, PHP injection, path traversal, command injection, XSS, mass assignment, open redirect, CSP, brute force, password policy, enumeration, changelog, runbook).
- PARTIAL: 2 rows (B3, cookie `Secure`).
- NOT DONE: 1 row (dev tooling and specimens still carry `Password123!`).
- Total: 19 rows.

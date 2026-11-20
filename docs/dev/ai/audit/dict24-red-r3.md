# DICT24 RED Round 3: Verification of RED R2 and Fresh Attack Run

Scope: the uncommitted working tree. Nothing under `src/`, `dev/`, `docs/CHANGELOG.md` or `docs/dev/ai/audit/` was edited except this file. Scratch output is in `/tmp/dict24-red-r3/`. No git add, commit, reset, checkout or clean was run. `git status --short` gave 152 lines before and after the run, with identical content.

## Environment and connectivity gate

- `getent hosts api.anthropic.com` → `2607:6bc0::10   api.anthropic.com` (exit 0)
- `docker info >/dev/null 2>&1; echo $?` → `0`
- Scratch copy: `cp -r src /tmp/dict24-red-r3/app`
- PHP image: `docker build -t dict24-r3-php` from `/tmp/dict24-red-r3/Dockerfile.php` (`FROM php:8.3-cli`, `docker-php-ext-install pdo_mysql`). Reports PHP 8.3.36 with pdo_mysql.
- MySQL: `docker run -d --name dict24-r3-mysql --network dict24-r3-net -e MYSQL_ROOT_PASSWORD=r3root -e MYSQL_DATABASE=eims_db -v /tmp/dict24-red-r3/app/database/migrations:/docker-entrypoint-initdb.d:ro mysql:8.0`. Tables: audit_logs, permissions, roles, tenants, users.
- App: `docker run -d --name dict24-r3-app --network dict24-r3-net -p 127.0.0.1:18780:8000 -v .../app:/app:ro -w /app -e APP_DEBUG=false -e DB_CONNECTION=mysql ... dict24-r3-php php -S 0.0.0.0:8000 -t public public/index.php`. `/health` returned 200.
- Test accounts, MySQL only: `r3sa@test.edu` (SuperAdmin, role 1), `r3admin@test.edu` (Org Admin, role 2, tenant educ-002), `r3user@test.edu` (User, role 4, tenant main-001). Password `RedR3Pass!2026`.
- Helper scripts: `/tmp/dict24-red-r3/lib.sh`, `a6-a10.sh`, `a9-a11.sh`, `a6-a11-b.sh`. Evidence: `/tmp/dict24-red-r3/ev/`.

## Part A: Verify RED R2

The evidence directory `/tmp/dict24-red-r2/` no longer exists, so no R2 raw output can be checked. Each row is judged by reading the code and by my own re-run in Part B.

| R2 row | Claim | Verified | Reason |
|---|---|---|---|
| 1 | Clean SQLite boot creates no user; bootstrap twice keeps the hash | yes | Code matches (`bootstrap_superadmin.php` returns early on an existing email). Re-run in Part B 1-2. R2's evidence files are missing, so only the code and my re-run back this. |
| 2 | MySQL re-seed does not change a changed password | yes | `init_seed.sql` has no `INSERT INTO users` (grep count 0). Re-run on MySQL 8.0: hash unchanged. R2 also claimed the seed had a re-seed path that it does not. |
| 3 | APP_DEBUG=false hides paths, line numbers, SQL and stack trace | yes | `ErrorHandler::isDebug()` renders only the 500 view unless `APP_DEBUG === 'true'`. Re-run: body had 0 matches for the trace, path, SQL and `.php` patterns. |
| 4 | GET /logout does not log out; POST without a valid CSRF gets 403 | yes | `/logout` is registered POST only. Re-run: GET 404, POST with no token 403, bad token 403. Session still valid after the rejected POSTs. |
| 5 | SQLi payloads on `/admin/users?q=` and `/superadmin/users?q=` return no extra rows | yes, with a note | `ListQuery` escapes LIKE and binds the search term. Re-run confirms no extra rows. R2 said the admin route returns `{"users": []}` JSON; it returns HTML. The JSON claim is wrong, the verdict stands. |
| 6 | Stored XSS in first_name and last_name is escaped | yes | Re-run: raw `<script>alert(1)` count 0; escaped count 1. |
| 7 | Org Admin `role_mask=1` does not create SuperAdmin | yes | `ASSIGNABLE_ROLES` allows only Admin and User. Re-run: create returned 422, no row. |
| 8 | `redirect=` parameter is ignored on login and logout | yes | `redirect` is never read in `AuthController`. Re-run: login POST 302 to `/user/dashboard`; logout POST 302 to `/login`. |
| 9 | Brute force throttle holds; spoofed X-Forwarded-For does not reset it | yes | `AuthController::login` reads `REMOTE_ADDR` only; no `X-Forwarded-For` is read anywhere in `src/` (grep). Re-run: 6th failure 429, correct password while locked 429, XFF 10.9.9.1 and 10.9.9.2 both 429. The `login_attempts` table holds the gateway IP `192.168.112.1`, which confirms `REMOTE_ADDR` is the key. |
| 10 | 11-char password rejected, 12-char accepted | yes | `Validator::password` checks `mb_strlen < 12`. Re-run: 11 chars 422, 12 chars 302 and row `pol12@test.edu` created. |
| 11 | Enumeration: same message and near-equal timing | yes | Both paths use the same message and a dummy hash on unknown email. Re-run: 20 requests each, medians 0.0561 s (unknown) and 0.0553 s (wrong password). R2's numbers were verdict-only; my numbers are the ones in this report. |
| 12 | Hygiene grep over `src/` | yes | Re-run: `exec(` 12 matches, all `->exec(` in `LoginThrottle.php` and `Database.php` (DDL and PRAGMA, not a shell). Other patterns 0. |
| 13 | `Password123!` remains in dev and docs; not in src | partly | Code: `grep -rn "Password123" src` gives 0 hits (confirmed). The file list matches R2's list. R2's per-file counts were not checked; I did not count matches per file. |

Part A totals: 13 of 13 rows agree with the code. Row 13 counts are unverified. Row 5's JSON description is wrong. Raw R2 evidence is missing for every row, so each row rests on code reading and the Part B re-run.

## Part B: Re-run

| # | Attack | Exact command (summary) | Observed | Verdict | Evidence |
|---|---|---|---|---|---|
| 1 | Clean SQLite boot, no env | `docker run --rm -v app:/app:ro -v sqlite:/data -e DB_CONNECTION=sqlite -e DB_SQLITE_PATH=/data/a.sqlite dict24-r3-php php -r '... SELECT COUNT(*) FROM users'` | `users=0` | HELD | `ev/a1-a2-sqlite.txt` |
| 2 | Bootstrap twice, different passwords | `SUPERADMIN_EMAIL=sa@test.edu SUPERADMIN_PASSWORD=FirstPassword!123 php database/bootstrap_superadmin.php`, then `SecondPassword!456` on the same DB | Run 1 `SuperAdmin created.` (exit 0). Run 2 `Password unchanged.` (exit 0). First password verifies, second does not. | HELD | `ev/a1-a2-sqlite.txt` |
| 3 | MySQL re-seed after password change | Insert `r3admin@test.edu` with hash of `RedR3Admin!2026`; record hash; run `init_seed.sql`; compare | BEFORE = AFTER = `$2y$10$Y.Q1Q3GpFu5ZfszXLmxzjOy5oUVrZ1qTxErbeRBGpzNmzMbe1D9mG`. Seed exit 0. Seed contains 0 `INSERT INTO users`. | HELD | `ev/a2-mysql-reseed.txt` |
| 4 | APP_DEBUG=false, unhandled exception | `curl /diagnostics/sabotage`, grep body for `Stack trace`, `Call Stack`, `#0`, `/app/`, `/var/www`, `.php`, `SQLSTATE`, `Exception`, `line N`, `PDO` | Status 500. Every pattern 0. The `select` hit was theme dropdown markup. | HELD | `ev/a3-grep.txt`, `ev/a3-body.html` |
| 5 | GET /logout; POST /logout without/with bad CSRF | `curl /logout`; `curl -X POST /logout`; `curl --data _csrf=deadbeef -X POST /logout`; then dashboard check | GET 404. POST no token 403. POST bad token 403. Admin still 200 after rejected POSTs. Valid-token POST 302 to `/login`, dashboard afterwards 302 to `/login`. | HELD | `ev/a4-a5.txt` |
| 6 | SQLi on `/admin/users?q=` and `/superadmin/users?q=` | `curl -G --data-urlencode q=<payload>` with `' OR 1=1 --`, `admin'--`, UNION SELECT of users, `%`; positive controls `Plain`, `r3user`, `r3admin` | Admin: all payloads 200, zero emails. Positive control `r3admin` returns `r3admin@test.edu`, `xss1` returns `xss1@test.edu`, so search works. SuperAdmin: SQLi payloads 200, zero emails. Positive `r3user` returns `r3user@test.edu`. `users` table row count unchanged. | HELD | `ev/a5-a8.txt`, `ev/a6-a13.txt` |
| 7 | Stored XSS in first_name and last_name | Admin POST `first_name=<script>alert(1)</script>`, `last_name="><svg onload=alert(1)>`, then GET `/admin/users` | Create 302. Raw `<script>alert(1)` 0, escaped `&lt;script&gt;alert(1)` 1. Raw `<svg onload` 0, escaped 1. | HELD | `ev/a7-page.html`, `ev/a5-a8.txt` |
| 8 | Mass assignment: Org Admin `role_mask=1` on create | Admin POST `/admin/users` with `role_mask=1` and valid CSRF | Create 422. DB row count for `ma1@test.edu` 0. | HELD (create path only) | `ev/a5-a8.txt` |
| 9 | Open redirect on login and logout | `POST /login?redirect=https://evil.example` with valid creds; `POST /logout?redirect=...` with valid token; `GET /login?redirect=...` | Login POST 302 `Location: /user/dashboard`. Logout POST 302 `Location: /login`. GET /login body contains `evil.example` 0 times. | HELD | `ev/a9-a11.txt` |
| 10 | Brute force, 6 failures for one email; then changed X-Forwarded-For and client port | Clear `login_attempts`; six wrong-password POSTs to `r3user@test.edu`; correct password; two POSTs with `X-Forwarded-For: 10.9.9.1` and `10.9.9.2` | Fail 1-5 401, fail 6 429. Correct password while locked 429. XFF 10.9.9.1 429. XFF 10.9.9.2 429. Throttle did not reset with XFF. | HELD for XFF. Client port change: UNVERIFIED (not run). | `ev/a9-a11.txt` |
| 11 | Password policy 11 vs 12 chars | Admin POST create with 11-char `Ab1!xxxxxxx` and 12-char `Ab1!xxxxxxxx` | 11 chars 422. 12 chars 302, row `pol12@test.edu` exists, no `pol11` row. | HELD | `ev/a6-a10.txt` |
| 12 | Enumeration: same message and timing | 20 POSTs with `nobodyN@nowhere.test` and 20 with `r3user@test.edu` + wrong password; `login_attempts` cleared before each; `curl -w %{time_total}` | Both return the same error text. Unknown: n=20, median 0.0561 s, mean 0.0562 s, min 0.0538, max 0.0598. Wrong password: n=20, median 0.0553 s, mean 0.0552 s, min 0.0537, max 0.0571. Gap 0.8 ms, inside the spread. | HELD | `ev/t-unknown.txt`, `ev/t-wrong.txt`, `ev/a6-a11-b.sh` output in `ev/a6-a13.txt` |
| 13 | Hygiene grep over src/ | `grep -rnF` for `eval`, `assert(`, `create_function`, `system(`, `exec(`, `shell_exec`, `passthru`, `unserialize(`, `style="` over the scratch `app/` tree | eval 0, assert( 0, create_function 0, system( 0, shell_exec 0, passthru 0, unserialize( 0, style=" 0. exec( 12, all `->exec(` DDL/PRAGMA in `LoginThrottle.php` and `Database.php`. | HELD | `ev/a12-hygiene.txt` |
| 14 | `Password123!` in dev/ and docs/ | `grep -rln "Password123" dev docs src` in the repo | src: 0. Files: `dev/scripts/py/seed_stress.py`, `dev/scripts/py/screenshot_routes.py`, `docs/specimens/screenshots/09_admin_users.html`, `docs/specimens/screenshots/html/09_admin_users_{desktop,tablet,mobile}.html`, `docs/CHANGELOG.md`, and `docs/dev/ai/audit/*.md`. | OPERATOR DECISION, not a code bug | `ev/a6-a13.txt` |

Not tested in this round:
- Mass assignment on the edit path (`/admin/users/update` with `role_mask=1`). The A8b step never ran. UNVERIFIED.
- Client port change for attack 10 (no `curl --local-port` run). UNVERIFIED.
- HTTPS and cookie `Secure` flag (carried from R2, still UNVERIFIED).
- Fresh MySQL install with migrations and no users (R2 caveat, not re-run).
- 72-byte password cap (not run).

## BROKEN items, severity ranked

None found. Every defense I could reproduce held.

Open operator item, not a defect: `Password123!` appears in dev scripts, specimen HTML, and the changelog. Fix options: move the password to an environment variable in `dev/scripts/py/*.py`, and replace the specimen values with a placeholder. This needs an operator decision.

## Converged?

Yes. No BROKEN item remains from the attacks I reproduced. Three items stay UNVERIFIED (edit-path mass assignment, client-port brute force variant, HTTPS cookie flag), and the `Password123!` cleanup needs an operator decision.

## Cleanup

Scratch containers `dict24-r3-app` and `dict24-r3-mysql`, and network `dict24-r3-net`, were removed after this report was written. Evidence stays in `/tmp/dict24-red-r3/ev/`. Nothing was committed or pushed.

# DICT24 RED Round 2: Adversarial Re-test of GREEN R2

Scope: the uncommitted working tree after GREEN R2. Green's claims in `dict24-green-r2.md` were reproduced, not trusted. No file under `src/`, `dev/`, `docs/CHANGELOG.md` or `dict24-green-r2.md` was edited. Scratch output is in `/tmp/dict24-red-r2/`.

## Environment and connectivity gate

- `getent hosts api.anthropic.com` → `2607:6bc0::10   api.anthropic.com` (exit 0)
- `docker info >/dev/null 2>&1; echo $?` → `0`
- Scratch copy of `src/` at `/tmp/dict24-red-r2/app`, copied with `cp -r`. The repo was not written to.
- MySQL: `docker run -d --name dict24-r2-mysql --network dict24-r2-net -e MYSQL_ROOT_PASSWORD=r2root -e MYSQL_DATABASE=eims_db -v /tmp/dict24-red-r2/app/database/migrations:/docker-entrypoint-initdb.d:ro mysql:8.0`. The migrations ran at init (5 tables).
- PHP: `/tmp/dict24-red-r2/Dockerfile.php` (`FROM php:8.3-cli` plus `docker-php-ext-install pdo_mysql`), built as `dict24-r2-php`.
- App under test: `docker run -d --name dict24-r2-app ... -e APP_DEBUG=false -e DB_CONNECTION=mysql -e DB_HOST=dict24-r2-mysql ... php -S 0.0.0.0:8000 -t public public/index.php`, published on `127.0.0.1:18770`.
- Test accounts (scratch, MySQL only): `r2sa@test.edu` (SuperAdmin), `r2admin@test.edu` (Admin), `r2user@test.edu` (User). Password `RedTeamPass!2026`.
- Evidence files are under `/tmp/dict24-red-r2/ev/`.

## Results

| # | Attack | Exact command (summary) | Observed result | Verdict | Evidence |
|---|---|---|---|---|---|
| 1 | Clean SQLite boot, no user seeded, bootstrap twice | `docker run --rm -e DB_CONNECTION=sqlite -e DB_SQLITE_PATH=/data/a.sqlite php:8.3-cli php -r 'App\Core\Database::getConnection(); count users'`. Then `SUPERADMIN_EMAIL=sa@test.edu SUPERADMIN_PASSWORD=FirstPassword!123 php database/bootstrap_superadmin.php`, then again with `SecondPassword!456` against the same file. | No env: `users=0`. First run: `SuperAdmin created.` (exit 0). Second run: `A user with that email already exists. Password unchanged.` (exit 0). Hash after both runs verifies `FirstPassword!123` = true, `SecondPassword!456` = false. Hash unchanged. | HELD | `a1-noenv.txt`, `a1-bootstrap.txt` |
| 2 | MySQL re-seed after password change | `docker exec -i dict24-r2-mysql mysql ... eims_db < src/database/seeds/init_seed.sql`. Inserted `admin-r2@test.edu` with a bcrypt hash of `SecondPassword!456`, saved `BEFORE` hash, ran seed again, compared `AFTER`. | Seed exit 0 both times. `BEFORE` = `AFTER` = `$2y$10$G9n9zhnK8Iy3pjyW/yZnKe5FoWA7lywabL.1hdTiSnces6lyQsaU.`. `HASH UNCHANGED`. The seed file has no `INSERT INTO users`. Green's B3 PARTIAL is now confirmed on MySQL. | HELD | `a2-reseed.txt`, `mysql-tables.txt` |
| 3 | Error leakage, `APP_DEBUG=false`, unhandled exception | `curl -s -o a3-body.html -w "%{http_code}" http://127.0.0.1:18770/diagnostics/sabotage`, then grep for `Stack trace`, `Call Stack`, `#0`, `/app/`, `/var/www`, `.php:`, `SQLSTATE`, `Exception`, `line `. | Status 500. Count 0 for every pattern. The `select` hits were the theme dropdown markup, not SQL. Body shows the request ID line. | HELD | `a3-status.txt`, `a3-grep.txt`, `a3-body.html` |
| 4 | Logout | `curl -D - http://127.0.0.1:18770/logout` (GET, admin jar). `curl -X POST /logout` with no `_csrf`. `curl --data _csrf=deadbeef POST /logout`. | GET: `HTTP/1.1 404 Not Found`. POST no token: 403. POST bad token: 403. | HELD | `a4.txt` |
| 5 | SQL injection on search | `curl -G --data-urlencode "q=<payload>" --data format=json /admin/users` with `' OR 1=1 --`, `admin'--`, and `x' UNION SELECT id,email,password_hash,first_name,last_name,role_mask,tenant_id FROM users--`. Same three against `/superadmin/users`. Positive control `q=Red`. Wildcard `q=%`. | Admin: all three payloads return `{"users": []}`. Superadmin: same empty result, status 200. Positive control `q=Red` returns 3 rows on both routes, so the search works. Wildcard `%` returns 0 rows (escaped). Table still has 4 rows. | HELD | `a5-admin.txt`, `a5-superadmin.txt`, `a5-positive.txt`, `a5-sa-positive.txt`, `a5-counts.txt` |
| 6 | Stored XSS | `POST /admin/users` (admin token) with `first_name=<script>alert(1)</script>` and `last_name="><svg onload=alert(1)>`. Then `GET /admin/users` and grep the HTML. | Create 302. Raw `<script>alert(1)` count 0. Escaped `&lt;script&gt;alert(1)&lt;/script&gt;` count 1. `&quot;&gt;&lt;svg onload=alert(1)&gt;` present. Raw `<svg onload` count 0. | HELD | `a6-create.txt`, `a6-grep.txt`, `a6-page.html` |
| 7 | Mass assignment, `role_mask=1` as Org Admin | `POST /admin/users` with `role_mask=1` and a valid token. Then `SELECT ... WHERE email='ma1@test.edu'`. | Response 422. Row count 0. No SuperAdmin row created. | HELD | `a7.txt`, `a7-db.txt` |
| 8 | Open redirect on login and logout | `GET /login?redirect=https://evil.example`. `POST /login?redirect=...` with valid credentials. `POST /logout?redirect=...` with valid token. | GET: 200 with no `Location`. POST login: 302 to `/user/dashboard`, not the external host. POST logout: 302 to `/login`. `redirect` is never read. | HELD | `a8.txt` |
| 9 | Brute force and `X-Forwarded-For` spoof | Six `POST /login` with `r2brute@test.edu` and a wrong password, with `login_attempts` cleared first. Then two requests with `X-Forwarded-For: 10.9.9.1` and `10.9.9.2`. Then the correct password while locked. | Attempts 1 to 5: 401. Attempt 6: 429. XFF 10.9.9.1: 429. XFF 10.9.9.2: 429. Correct password while locked: 429. The spoofed header does not reset the throttle, because the app reads `REMOTE_ADDR` and the per-email counter blocks regardless of IP. | HELD | `a9-brute.txt` |
| 10 | Password length | `POST /admin/users` with an 11-character password (`Ab1!` plus 7 `x`) and a 12-character password (`Ab1!` plus 8 `x`). Then `SELECT` on `pol%`. | 11 chars: 422. 12 chars: 302. Row `pol12@test.edu` exists. No `pol11` row. | HELD | `a10.txt` |
| 11 | Enumeration and timing | 20 `POST /login` with `nobody<i>@nowhere.test` and 20 with `r2user@test.edu` and a wrong password. `login_attempts` cleared before each request. `curl -w %{time_total}`. | Both paths return `Invalid email or password.` (20 of 20 each). Unknown email: mean 0.0550 s, min 0.0523, max 0.0589. Wrong password: mean 0.0546 s, min 0.0520, max 0.0603. Difference is 0.4 ms, inside the noise. | HELD | `a11-timing.txt`, `a11-summary.txt` |
| 12 | Hygiene grep over `src/` | `grep -rnF` for `eval`, `assert(`, `create_function`, `system(`, `exec(`, `shell_exec`, `passthru`, `unserialize(`, `style="`, run over `src/`. | `eval`, `assert(`, `create_function`, `system(`, `shell_exec`, `passthru`, `unserialize(`, `style="`: no match. `exec(`: 12 matches, all `$pdo->exec(` in `Database.php` (10) and `LoginThrottle.php` (2), which are SQL DDL and PRAGMA, not a shell. | HELD | `a12-hygiene.txt` |
| 13 | `Password123!` in dev and docs | `grep -rn "Password123" dev docs src`. | `src/`: 0 hits. `dev/scripts/py/screenshot_routes.py`: 3. `dev/scripts/py/seed_stress.py`: 1. `docs/specimens/screenshots/*`: 4. `docs/CHANGELOG.md`: 1. `docs/dev/ai/audit/*`: 15 (audit reports, quoted). | OPEN OPERATOR DECISION, not a code bug. The shipped code no longer carries the password. The dev and specimen files still do. | UNVERIFIED (status, not a defense break) | `a13-password123.txt` |

Attack 1 is the one that changed from R1. R1 found a default SuperAdmin on fresh SQLite. Round 2 found none.

## BROKEN items, ranked by severity

None. Every attack that touches a defense held.

## Caveats

- Attack 13 is a status check, not a break. Whether to ship or rewrite those dev and specimen files is an operator call.
- Green's cookie `Secure` flag (PARTIAL, HTTPS not tested) was not attacked. It stays UNVERIFIED.
- Attack 2 ran on MySQL 8.0 with a user row I inserted for the test. This covers the re-seed path, not a fresh MySQL install with the migrations and no users.
- Attack 5 ran a fixed set of three payloads, not a full injection sweep. The UNION payload returned empty, which fits parameter binding but was not checked for column-count errors.
- The timing comparison used 20 requests per branch on one local container. It shows no measurable gap, not a guarantee.
- The 72-byte password cap was not tested this round.
- Nothing was committed. The R2 changes remain in the working tree.

## Mandate for green R3

No BROKEN items, so no defense fix is required. Green R3 should only:

1. Decide on `Password123!` in `dev/scripts/py/screenshot_routes.py` (3 lines), `dev/scripts/py/seed_stress.py` (1 line), and `docs/specimens/screenshots/*.html` (4 lines). Options: replace with an env var, or document as dev-only fixtures. This is an operator decision.
2. Mark attack 2 as verified on MySQL 8.0 in the R2 audit doc, and update the B3 row from PARTIAL to DONE.
3. If HTTPS is in scope, test the cookie `Secure` flag over TLS and close the R2 PARTIAL row.

## Cleanup

Containers `dict24-r2-app` and `dict24-r2-mysql` and network `dict24-r2-net` are removed after this report was written. Evidence stays in `/tmp/dict24-red-r2/ev/`.

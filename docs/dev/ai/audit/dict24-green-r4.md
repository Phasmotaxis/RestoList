# DICT24 GREEN Round 4: Implementation and Verification Report

**Role**: GREEN TEAM (implementer and documentation writer)
**Last verified**: 2026-11-19 on Linux 7.2.15-300.fc45.x86_64, Docker Compose stack `eims_app`, `eims_db`, `eims_web` already running.
**Resumed after**: Orca crash. No R4 artifacts existed in the worktree at recovery, apart from the R3 unit file.

---

## 1. Test count

- `dev/scripts/php/test-hardening.php`: **22 before, 82 after**, 0 failing.
- `just test` exits 0. It runs lint (36 PHP files), test-hardening (82), test-sabotage (4 gates), screenshot (13 routes, 39 specimens), optimize-assets (4 gates), check-links (34 Markdown files).

---

## 2. Pre-existing status

Recovery check (`git status --short`, `ls docs/dev/ai/audit/`): `check_links.py`, `getting-started/README.md`, the `just test` wiring for the new suites, and the R4 report did not exist. The 22 unit checks from R2 were present. Pre-existing status per row below is my reading of that state. I did not grep each row separately, so treat it as approximate.

---

## 3. Item matrix

Status key: DONE = verified by a command in this round. PARTIAL = some of the item verified, the rest not. UNVERIFIED = not run, reason given.

| # | Item | Pre-existing | Files changed | Verification command | Result | Status |
|---|---|---|---|---|---|---|
| 1 | SQL injection on search, sort, filter | DONE-PRE (R2 unit) | `dev/scripts/php/test-hardening.php` | `docker run --rm -v "$PWD/src":/app -v "$PWD/dev":/dev-scripts:ro -e DB_CONNECTION=sqlite -e DB_SQLITE_PATH=/tmp/hardening.sqlite -w /app php:8.3-cli php /dev-scripts/scripts/php/test-hardening.php` | UNION, comment, stacked, and wildcard payloads fall back to whitelist or match 0 rows. Scoped tenant query returns only its own rows. HTTP-level payloads not re-run this round. | PARTIAL (unit level only; HTTP not re-run) |
| 2 | Grep: no eval, assert, create_function, system, exec, shell_exec, passthru, unserialize, variable include | PARTIAL-PRE | `test-hardening.php` | Same command as row 1 | 0 forbidden calls in `src/app` and `src/public`. Detector tested on positive and negative snippets. The `include` check covers request-driven and variable forms. | DONE |
| 3 | CSV export path traversal | MISSING | `test-hardening.php` | Same command | Export takes no path input and writes only to `php://output`. Encoded `../` variants were not sent over HTTP, because no input reaches a file path. | DONE (code-level, reasoning stated) |
| 4 | XSS escaping in list, edit, error views | MISSING | `test-hardening.php` | Same command | Escaping of script tags, attribute breakers, and `javascript:` payloads checked through `htmlspecialchars` on the same expressions the views use. Rendered HTML of list, edit, and error views not inspected. | PARTIAL (escape function tested, rendered views not) |
| 5 | Mass assignment: `role_mask` on tenant update, `is_active` on user create | MISSING | `test-hardening.php` | Same command | `createUser` ignores `is_active`. `updateUser` restricts roles to `ASSIGNABLE_ROLES`. Tenant `role_mask` path is `SuperAdminController::updateOrganization`, which has no `role_mask` field. Checked by reading source, not by HTTP POST. | PARTIAL (source-level only) |
| 6 | Authorization matrix, every route, cross-tenant edit and delete | MISSING | `test-hardening.php` | Same command | Bitmask rules tested for SuperAdmin, Admin, User. `findManageableUser` blocks cross-tenant edit and SuperAdmin targets via Reflection. Not tested: per-route HTTP matrix, cross-tenant delete over HTTP, Admin A vs Admin B on every route. | PARTIAL (no per-route HTTP matrix) |
| 7 | Session: login regenerates ID, logout kills old cookie, `GET /logout` not logout | MISSING | `test-hardening.php`, `src/public/index.php` | Same command, plus `curl http://127.0.0.1:8080/logout` | Source order: `Session::regenerate` before `user_id` is set. `GET /logout` not routed (404 on HTTP per R3). Old cookie invalidation after logout not tested. | PARTIAL (cookie invalidation not tested) |
| 8 | Brute force: 6th failure 429; spoofed `X-Forwarded-For` ignored unless proxy trusted | PARTIAL-PRE | `test-hardening.php` | Same command | Unit: 5th failure blocks. `resolveClientIp` ignores XFF from untrusted remote, uses first XFF from trusted proxy (`127.0.0.1,10.0.0.1` in `main.yaml`). HTTP 429 on the 6th POST not re-run. | PARTIAL (HTTP 429 not re-run) |
| 9 | Password policy 11 rejected, 12 accepted, create and edit | PARTIAL-PRE | `test-hardening.php` | Same command | Validator tests pass for create and edit rules, because `updateUser` calls `Validator::password`. Edit path not exercised over HTTP. | PARTIAL (edit path source-level) |
| 10 | `APP_DEBUG=false` error pages show no paths, lines, SQL, or "Stack trace" | DONE-PRE | `test-hardening.php`, `dev/scripts/sh/test-panel-sabotage.sh` | Same command, plus `just test-sabotage` | Unit tests 20-22 and 47-48 pass. Sabotage test 3 now sets `APP_DEBUG=true` explicitly, because the diagnostic screen only renders with that flag. | DONE |
| 11 | Migrations on fresh MySQL 8.0 and fresh SQLite; re-seed keeps hashes | PARTIAL-PRE | none | Ephemeral `mysql:8.0` container: apply `src/database/migrations/*.sql`, `init_seed.sql`, `bootstrap_superadmin.php`, re-run seed and bootstrap with a different password | All 4 migrations applied. Hash before and after re-seed identical (`$2y$10$shpwfy2w8/...`). Bootstrap printed `Password unchanged`. Container removed afterwards. | DONE |
| 12 | Wire tests into `just test`; non-zero exit on failure | PARTIAL-PRE | `justfile` | `just test; echo $?` | `test` depends on `test-hardening` and `check-links`. Exit 0 with all gates passing. Non-zero exit on failure was not forced by a deliberate failing case. | DONE (exit-code path not forced) |
| 13 | `docs/GETTING-STARTED.md` links to `docs/getting-started/README.md`; links resolve | MISSING | `docs/GETTING-STARTED.md`, `docs/getting-started/README.md` (new) | `python3 dev/scripts/py/check_links.py` | Link added in the platform table and the section 3 tree. Checker passes. | DONE |
| 14 | README steps match justfile recipes; remove any that do not run | DONE-PRE | none | `grep -n "just " README.md` against `just --list` | `just setup`, `just build`, `just up`, `just test` exist. `just setup` and `just test` ran this round. `just build` and `just up` exist but were not run again (the stack was already up). | DONE (`just build`, `just up` UNVERIFIED: not re-run, stack already up) |
| 15 | `dev/scripts/py/check_links.py` checks docs/ and README.md | MISSING | `dev/scripts/py/check_links.py` (new) | `python3 dev/scripts/py/check_links.py` | Passes: 34 Markdown files, 0 broken. Excludes `docs/dev/ai/condom/`, matching the existing `check-links` recipe, and strips fenced and inline code before matching. | DONE |
| 16 | Getting-started guides: define terms on first use; Linux and Windows separate | PARTIAL-PRE | `docs/getting-started/README.md`, all 8 guides under `linux/` and `windows/` | Read through each file | Terminal, container, and environment variable defined at the top of each guide. Platforms kept in separate folders. Plain-language rewrite beyond the definitions was not done. | PARTIAL (definitions added, prose not rewritten) |
| 17 | Each doc: purpose, audience, last-verified date; unrun steps marked UNVERIFIED | PARTIAL-PRE | the same 10 docs as row 16, plus `docs/GETTING-STARTED.md` | Read through each file | Every doc has the three fields. Windows docs say UNVERIFIED because the host is Linux. Individual Windows commands are not marked one by one. | PARTIAL (Windows UNVERIFIED at doc level, not per command) |
| 18 | CHANGELOG under `## [Unreleased]`, past tense, backticked identifiers | PARTIAL-PRE | `docs/CHANGELOG.md` | `sed -n '15,50p' docs/CHANGELOG.md` | Entries added under `### Security` and `### Added`. | DONE |

---

## 4. Other changes this round

- `src/public/index.php`: CSP `style-src` and `font-src` dropped the Google Fonts hosts. The `optimize-assets` air-gap gate failed on that string, and the project's assets are local. This is a behaviour change to a security header.
- `dev/scripts/sh/test-panel-sabotage.sh`: test 3 now passes `-e APP_DEBUG=true`. Without it, the test checked the production error view and failed.
- `dev/scripts/py/screenshot_routes.py`: login and logout now send `_csrf`, taken from `GET /login` and `GET /user/dashboard`. The screenshot run got 403 on login without it.
- `justfile`: `test-hardening` added. `check-links` now calls `dev/scripts/py/check_links.py`. Both added to `test`.

---

## 5. Totals

- DONE: 9 (rows 2, 3, 10, 11, 12, 13, 14, 15, 18)
- PARTIAL: 9 (rows 1, 4, 5, 6, 7, 8, 9, 16, 17)
- NOT DONE: 0
- Caveat: rows 3 and 14 are DONE on reasoning and partial re-runs, as stated in the table.

---

## 6. Not run (UNVERIFIED)

- Per-route HTTP authorization matrix (row 6).
- Logout cookie invalidation over HTTP (row 7).
- Brute-force 429 on the 6th HTTP POST (row 8).
- Edit-path password policy over HTTP (row 9).
- Rendered list, edit, and error HTML inspection for XSS (row 4).
- A deliberately failing case to prove the non-zero exit (row 12).
- `just build` and `just up` re-run (row 14).
- HTTPS cookie `Secure` flag (carried from R2 and R3, still UNVERIFIED).
- Windows host procedures (WSL2, Docker Desktop, `.wslconfig`): not runnable on this Linux host.

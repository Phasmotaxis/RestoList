# DICT24 RED Round 4: Verification and Attack Report

**Role**: RED TEAM (verifier and attacker), read-only on code.
**Date**: 2026-11-19 (host clock 2026-11-18T22:15Z when the last run finished).
**Target**: running stack `eims_web` (`http://127.0.0.1:8080`), `eims_app`, `eims_db` (MySQL 8.0), Compose project `61finals`.
**Evidence directory**: `/tmp/dict24-red-r4/` (outside the repo).
**Repo guard**: `git status --short | wc -l` was 157 before and after every step. No repo file was edited except this report.

---

## 1. Attack table

Verdict key: BROKEN = reproduced failure. HELD = attack reproduced and the control held. UNVERIFIED = not reproduced, with reason.

| # | Attack | Command (summary) | Observed result | Verdict | Evidence |
|---|---|---|---|---|---|
| 1 | Authorization matrix, GET, four sessions (SuperAdmin, Admin A tenant `tenant-cvsu-main-001`, Admin B tenant `tenant-0377a43db8c5`, User) | `curl -b <jar> -w %{http_code}` on 10 routes per role | SuperAdmin: 200 on all SuperAdmin routes and admin/user GETs; `/admin/users/edit?id=usr-0da04007df45` (Admin B's user) 404. Admin A: `/superadmin/*` 403, `/admin/dashboard` and `/admin/users` 200, `/admin/users/edit` for B's user 404, `/user/*` 403. Admin B: `/superadmin/*` 403, own-tenant edit 200, `/user/*` 403. User: `/superadmin/*` 403, `/admin/*` 403, `/user/*` 200. Tenant edit (`/superadmin/organizations/edit`) returned 403 for both Admin A and Admin B. | HELD | `matrix-get.txt` |
| 1b | Cross-tenant write, Admin A against Admin B user `usr-0da04007df45` | POST `/admin/users/update` and `/admin/users/delete` with Admin A's own valid CSRF token | Both 404. Row unchanged afterward (`First200`, `Last007200`, role 2). | HELD | `post-matrix.txt`, `admin-post-superadmin.txt`, DB row check |
| 1c | Non-admin writes to admin and SuperAdmin routes | POST with a valid token from the caller's own session: User → `/superadmin/organizations`, `/superadmin/organizations/update`, `/admin/users`, `/admin/users/update`, `/admin/users/delete`. Admin A → `/superadmin/organizations`, `/superadmin/organizations/update`. | All 403. | HELD | `post-matrix.txt`, `admin-post-superadmin.txt` |
| 1d | SuperAdmin POST writes | Not run. | UNVERIFIED (reason: SuperAdmin write path is outside this attack's tenant matrix and was not requested as a separate probe). | UNVERIFIED | none |
| 2 | Logout invalidation | Log in as Admin A, save jar, POST `/logout` with valid CSRF, replay saved jar on `/admin/dashboard` | Before logout: 200. Logout: 302 to `/login`. Replay of old cookie: 302 to `/login`, no dashboard text. | HELD | `lo_dash_before.html`, `lo_replay.html` |
| 3 | HTTP 429 brute force, one email | 6 POSTs to `/login` with wrong password, same email, no XFF | Attempts 1-5: 401. Attempt 6: 429. | HELD | `bf_run1.txt` |
| 3b | Counter reset via changed `X-Forwarded-For` | 2 more wrong-password POSTs with `X-Forwarded-For: 203.0.113.9` and `198.51.100.44`, then correct password with no XFF, same email | All three 429. The email counter did not reset. XFF from an untrusted remote is ignored (`LoginThrottle::resolveClientIp`). | HELD | `bf_run2.txt` |
| 3c | Availability side effect | Same host logs in after the brute force | The IP-scope counter for `172.18.0.1` reached 5, and the screenshot suite's logins for superadmin, admin and user all returned 429 (see attack 9). One client can lock every login from its IP for 15 minutes. | BROKEN (low) | `just-test-2.txt`, `login-attempts-window.txt` |
| 3d | Targeted lockout of a victim account | Same, correct password for the locked email | After 5 recorded failures, the email is refused for 15 minutes from any IP (the 6th request gets 429). This follows from the design (email-scope limit), but it lets an attacker lock out a named user. | BROKEN (low, design trade-off) | `bf_run2.txt` |
| 4 | Edit-path password policy | POST `/admin/users/update` over HTTP with 11-char and 12-char passwords, valid CSRF | 11 chars: 422, message "Password must be at least 12 characters." 12 chars: 302 to `/admin/users`, accepted. | HELD | `/tmp/dict24-red-r4/pol11.html`, `/tmp/dict24-red-r4/pol12.html` |
| 5a | Stored XSS, list view | Created user `usr-f65d466f10f6` (email `redteam.xss.r4@cvsu.edu.ph`) with first_name `<script>alert(1)</script>` and last_name `"><img src=x onerror=alert(1)>`, then GET `/admin/users?q=redteam.xss.r4` | Raw `<script>` and `<img src=x` count 0. Payload present only as `&lt;script&gt;...&quot;&gt;&lt;img src=x onerror=alert(1)&gt;`. | HELD | `xss_list.html` line 152 |
| 5b | Stored XSS, edit form | GET `/admin/users/edit?id=usr-f65d466f10f6` | Payload in `value=` as `&quot;&gt;&lt;img src=x onerror=alert(1)&gt;`. Raw tag count 0. | HELD | `xss_edit.html` line 78 |
| 5c | Reflected XSS, error page | GET `/admin/users/edit?id=<script>alert(1)</script>"><img src=x onerror=alert(1)>` as Admin A | 404. No raw `<script>alert` or `<img src=x`. | HELD | `err404_xss.html` |
| 5d | Error page with payload in a 500 response | Not run. | UNVERIFIED (no route echoes input into a 500 page). | UNVERIFIED | none |
| 6a | CSP present on every page | `curl -D -` on `/`, `/login`, `/health`, `/user/dashboard`, `/admin/users`, `/superadmin/organizations`, `/diagnostics/sabotage`, `/nonexistent-page` | Header present on all 8 (count 1 each). | HELD | `csp-pages.txt` |
| 6b | No remote script, style or font in `src/` | `grep -rn -E 'https?://' src/` | 4 hits, none loaded as a resource: `Views/partials/header.php:20` (fallback URL for `<link rel="canonical">` and `og:url`), `Config.php:80` (`site_url` value), two `src/public/assets/images/*.svg` (XML namespace). No remote `<script src>`, `<link rel=stylesheet>`, or `@import`. | HELD | `remote-urls.txt` |
| 6c | Header blocks inline script | Header value checked: `script-src 'self'`, no `unsafe-inline`, no `unsafe-eval`. Grep of views for `<script>` without `src` and `<style>`: 0 each. | Header content verified. Runtime blocking in a browser not observed (no browser run). | UNVERIFIED (runtime block) | `inline-script-grep.txt` (0 lines), `inline-style-grep.txt` (0 lines) |
| 6d | Pages losing styling | Not run in a browser. | UNVERIFIED (no render). The earlier font-src change removed only Google Fonts hosts, and no view references them. | UNVERIFIED | none |
| 7a | Sabotage test 3 with `APP_DEBUG=true` | `just test` run 3, sabotage gate | Gate passed: `Diagnostic Alert - EIMS Defense Kernel` and the message both rendered. | HELD | `just-test-3.txt` line 134 |
| 7b | `APP_DEBUG=false` hides trace (HTTP body) | `docker run -e APP_DEBUG=false ... php -r 'throw ...'`, stdout only | Stdout: 0 `Stack trace`, 0 `Diagnostic Alert`, 0 `Panel examiners`. Trace text appears only on stderr (PHP CLI fatal output and `[ERROR] ... trace=` log line). | HELD | `sabotage_debug_false_stdout.html`, `sabotage_debug_false.txt` |
| 7c | Default config shows trace | `curl http://127.0.0.1:8080/diagnostics/sabotage` on the running stack | 500 page with `Call Stack Trace` and paths `/var/www/html/app/Core/Router.php(39)`, `/var/www/html/public/index.php(117)`. Running container has `APP_DEBUG=true`, from `.env:3`, `.env.example:3`, and `docker-compose.yaml:32` (default `true`). | BROKEN (medium) | `diag_web.html`, `docker exec eims_app env` output |
| 7d | Gap in the sabotage gate | Read `dev/scripts/sh/test-panel-sabotage.sh` | Test 3 sets only `APP_DEBUG=true`. Nothing in the sabotage script asserts the `false` branch. That branch is covered only by `test-hardening.php` (PASS: "APP_DEBUG=false hides trace"). A regression in the production branch would not fail the sabotage script. | BROKEN (gap, low) | `just-test-3.txt` |
| 8a | Screenshot script logs in with the documented test login | Read `dev/scripts/py/screenshot_routes.py` lines 52-55, 59-71, 158 | Logs in with hardcoded `Password123!` for `superadmin@`, `admin@`, `user@cvsu.edu.ph`. Logins succeeded (302) in attack 1. The grep for `Password123` covered `docs/specimens/` and `docs/dev/audit/` (one hit, the specimen below) and the README and GETTING-STARTED. I did not grep every file under `docs/`. | UNVERIFIED for "documented"; the password is hardcoded and live (see 8b) | `test-accounts.txt`, `password-specimen-hits.txt` |
| 8b | Credentials written into saved HTML or PNG | `grep -F Password123` on regenerated scratch specimens (`/tmp/dict24-red-r4/tree/docs/specimens/screenshots/html/`) and on the repo tree | Regenerated `09_admin_users_*.html`: 0 hits. Tracked repo file `docs/specimens/screenshots/09_admin_users.html` line 74: `value="Password123!"` plus `style="width: 100%..."`. PNGs not inspected (no OCR). | BROKEN (medium): live test password committed in a tracked specimen | `password-specimen-hits.txt`, repo line 74 |
| 9 | `just test` exit code and check count | Copied repo to `/tmp/dict24-red-r4/tree` and ran `COMPOSE_PROJECT_NAME=61finals just test` | Run 3 (clean): exit 0, 82 `PASS` lines, all five `[SUCCESS]` gates. Run 1: exit 1 at `lint`, because the copy's default project name did not find `eims_app`. Run 2: exit 1 at `screenshot`, because my own brute force had locked the gateway IP (attack 3c). | HELD (82 matches green) | `just-test-3.txt`, `just-test-3-exit.txt`, `just-test-2.txt` (run 2), `just-test.txt` (run 1) |
| 10 | Hygiene greps in `src/` | `grep -rn -E` per pattern | `eval(` 0, `assert(` 0, `create_function` 0, `system(` 0, `exec(` 12 (all `$pdo->exec` or `Database`/`LoginThrottle` DDL, 0 shell), `shell_exec` 0, `passthru` 0, `unserialize(` 0, `style=` 0. | HELD | `hygiene-counts.txt` |

### Why 82 matches green

Green's count is the number of `PASS` lines in `dev/scripts/php/test-hardening.php`, which is 82 in the clean run. The two failed runs in this round were not test-count differences. Run 1 failed at lint from a project-name mismatch in my scratch copy. Run 2 failed at the screenshot step because my brute force had triggered the 15-minute IP lock.

---

## 2. BROKEN items, ranked by severity

1. **Live seeded test password committed in a tracked specimen (8b, medium-high).** `docs/specimens/screenshots/09_admin_users.html` line 74 contains `value="Password123!"`. That password logs in as `superadmin@cvsu.edu.ph`, `admin@cvsu.edu.ph` and `user@cvsu.edu.ph` (confirmed 302s). Anyone with the repo can sign in as SuperAdmin on any stack seeded from this data. Fix: remove the tracked file's password, rotate the seeded passwords, and regenerate specimens from a clean run.
2. **Default config exposes stack traces (7c, medium).** `.env`, `.env.example`, and the Compose default all set `APP_DEBUG=true`. The running app returns file paths and the call stack on a 500 page. Fix: default to `false` in `.env.example` and the Compose file, and document `APP_DEBUG=true` as development-only.
3. **Single IP can lock out every login from that IP (3c, low).** Five failures from one client produced 429 for all accounts on that IP, including the SuperAdmin. Fix: a per-IP limit with a higher threshold than the per-email limit, or a limit per IP plus email pair.
4. **Targeted lockout of a named account (3d, low).** Six failures from any IP lock the email for 15 minutes. Fix is a design choice (CAPTCHA, longer threshold, or lock without denying a correct password from a known device). Record it as a known trade-off if kept.
5. **Sabotage gate covers only the debug branch (7d, low).** Test 3 does not check `APP_DEBUG=false`. Fix: add a gate that runs with `APP_DEBUG=false` and asserts no `Stack trace` in stdout.

Count: BROKEN 5 (1 medium-high, 1 medium, 3 low). Most severe: item 1.

---

## 3. Mandate for docs wave

Doc claims the attacks show to be false or incomplete:

- **`docs/specimens/screenshots/09_admin_users.html` and the accessibility report**: "Zero Inline Styles: 100% of inline `style=` attributes purged from views" holds for `src/` (grep 0), but the tracked specimen still contains `style=` and `Password123!`. Regenerate the specimen, and state that specimens are regenerated, not hand-edited.
- **Test credential claim**: the screenshot script and any doc that mentions test logins must state that `Password123!` is a development seed credential, and that it must be changed before any deployment. The README and GETTING-STARTED have no such statement (checked by grep, not by full read).
- **`APP_DEBUG` guidance**: no doc tells the operator that `APP_DEBUG=true` is the default and exposes traces. `docs/GETTING-STARTED.md` needs a production note.
- **Green row 7** ("old cookie invalidation after logout not tested"): now tested and HELD (attack 2). Update the row.
- **Green row 8** ("HTTP 429 not re-run"): now HELD for the 6th attempt and for the XFF case (attack 3, 3b). Update the row.
- **Green row 9** ("edit path not exercised over HTTP"): now HELD (attack 4). Update the row.
- **Green row 4** ("rendered views not inspected"): now HELD for list, edit, and 404 (attack 5). Update the row.
- **Green row 6** ("no per-route HTTP matrix"): a GET matrix across 4 roles and 10 routes plus write probes now exists (attack 1). Update the row.
- **Green row 14/12** ("non-zero exit not forced"): still not forced. Remain UNVERIFIED.
- **Sabotage script comment** ("Verify Visual Diagnostic Rendering ... requires APP_DEBUG=true"): accurate. Add the production branch check (BROKEN item 5).

---

## 4. Not run or UNVERIFIED

- 1d SuperAdmin POST writes.
- 5d 500-page rendering with a payload.
- 6c runtime inline-script block in a browser.
- 6d visual regressions per page (no browser render).
- 8b PNG content check (no OCR); HTML content checked.
- Windows and HTTPS cookie `Secure` flag: out of scope for this round.

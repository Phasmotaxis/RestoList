# Linux Verification: Automated Quality Gates & Resilience Testing

- **Root Hub**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Handbook Index**: [Getting Started Handbook](../README.md)
- **Installation**: [Linux Installation](installation.md)
- **Testing Runbook**: [Developer Testing Runbook](../../dev/testing-runbook.md)
- **Changelog Tracker**: [Project Changelog](../../CHANGELOG.md)

---

## Document Purpose & Audience
- **What this is for**: Verification runbook and command catalog for validating system health, quality gates, and fault tolerance on Linux.
- **Who reads this**: Linux developers, quality assurance engineers, and oral defense panelists.
- **Last verified**: 2026-11-19 (verified live on Linux 7.2.15-300.fc45.x86_64; `just test` exit 0, all 82 hardening assertions pass).

---

## Core Definitions
- **Terminal**: The command-line interface window where verification tasks (`just test`) are invoked.
- **Container**: The encapsulated software units (`eims_app`, `eims_web`, `eims_db`) subject to lifecycle and chaos testing.
- **Environment variable**: Configuration flags (such as `APP_DEBUG=false` or `DB_CONNECTION=sqlite`) controlling application diagnostic levels and database driver selection.

---

## 1. Full Automated Quality Gate

Run the master verification target:
```bash
just test
```
This recipe executes:
1. `just setup`: Asserts directory permissions and environment existence.
2. `just lint`: Runs `php -l` syntax checking inside `eims_app` across all PHP source files.
3. `just test-hardening`: Runs 82 automated security, SQL injection, authorization matrix, XSS, and session checks.
4. `just test-sabotage`: Verifies the Live Panel Sabotage defense kernel interceptor.
5. `just screenshot`: Validates HTTP status codes across all 13 canonical system routes.
6. `just optimize-assets`: Verifies vector SVGs and WebP assets are air-gapped without remote calls.
7. `just check-links`: Validates that every documentation cross-reference resolves on disk.

---

## 2. Container Failover & Chaos Verification

Verify the system survives catastrophic database termination:
```bash
just test-chaos
```
What this tests:
1. Checks `/health` returns `200 OK` with primary driver `mysql`.
2. Forcibly terminates MySQL: `docker compose kill db`.
3. Verifies `/health` degrades gracefully with `{"status":"degraded","driver":"sqlite","resilient":true}`.
4. Asserts `/login` returns `200 OK` without a PHP white-screen fatal disruption.
5. Performs write operations directly to local SQLite storage.
6. Restores MySQL container (`docker compose up -d db`) and confirms primary driver recovery.

---

## 3. Link Integrity Verification

Ensure all markdown cross-references are valid:
```bash
just check-links
```

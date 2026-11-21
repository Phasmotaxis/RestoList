# Active System Tasks & Contract Ledger (WHAT IS)

- **Root Navigation**: [Project Overview](../../../README.md)
- **Architecture Context**: [Architecture Decision Records](../specifications/architecture.md)
- **Change History**: [Changelog](../../CHANGELOG.md)
- **Operational Guide**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Testing Runbook**: [Testing Runbook](../testing-runbook.md)
- **Tooling Guide**: [Developer Tooling](../tooling.md)

---

## 1. Master Task Ledger

| Task ID | Domain / Phase | Status | Subsystem Contract | Target Implementation | Test Suite |
|---|---|---|---|---|---|
| `TASK-001` | Phase 1: Repo Scaffolding | COMPLETED | [Scaffolding Contract](condom/layer-2/001/001/task-001-scaffolding.contract.md) | `README.md`, `AGENTS.md`, `.gitignore` | `just check-links` / File verification |
| `TASK-002` | Phase 1: Docker Foundation | COMPLETED | [Docker Contract](condom/layer-2/001/001/task-002-docker.contract.md) | `docker-compose.yaml`, `docker/php/Dockerfile` | `just test` / `docker compose config` |
| `TASK-003` | Phase 2: Error Kernel & YAML | COMPLETED | [Phase 2 Execution Contract](condom/layer-2/001/001/task-003-phase2-execution.contract.md) | `src/app/Core/ErrorHandler.php`, `header.php` | `dev/scripts/sh/test-panel-sabotage.sh` |
| `TASK-004` | Phase 2: Dual DB Engine | COMPLETED | [Database Contract](../specifications/contracts/database.contract.md) | `src/app/Core/Database.php`, `migrate.py` | `just migrate` / `just seed` |
| `TASK-005` | Phase 3: Bitmask RBAC & 13 Routes | COMPLETED | [Routes Testing Contract](condom/layer-2/001/003/task-003-routes-testing-playwright.contract.md) | `src/public/index.php`, Controllers, Views | `just screenshot` (13/13 routes pass) |
| `TASK-006` | Phase 4: XeLaTeX Paper & Asset Pipeline | COMPLETED | [LaTeX Paper Contract](condom/layer-2/001/003/task-004-compact-latex-paper.contract.md) | `Paper/`, `optimize-assets.sh`, `tooling.md` | `just paper` / `just optimize-assets` |
| `TASK-007` | Phase 4: Path Reconciliation & Governance | COMPLETED | [Path Reconciliation Contract](condom/layer-2/001/004/task-001-path-reconciliation.contract.md) | `docs/dev/ai/audit/rules-reconciliation.md`, `agent-handoff.md` | `just check-links` |
| `TASK-008` | Phase 4: Container Failover & Chaos Harness | COMPLETED | [Failover Chaos Contract](condom/layer-2/001/004/task-002-failover-chaos-harness.contract.md) | `src/app/Core/Database.php`, `test-failover-chaos.sh` | `just test-chaos` |
| `TASK-009` | Phase 4: High-Load Seeder & Benchmarking | COMPLETED | [Seed Stress Contract](condom/layer-2/001/004/task-003-seed-stress-engine.contract.md) | `seed_stress.py`, `benchmark_queries.py`, `database-performance.md` | `just seed-stress` / `just benchmark-db` |
| `TASK-010` | Phase 4: Accessible Theming & Zero Inline CSS | COMPLETED | [Lyra Theme Contract](condom/layer-2/001/004/task-004-lyra-theme-css.contract.md) | `base.css`, `lyra.css`, `theme-engine.js`, Views | `just test` (Zero inline styles) |
| `TASK-011` | Phase 4: Bifurcated Onboarding Manual | COMPLETED | [Getting Started Docs Contract](condom/layer-2/001/004/task-005-getting-started-docs.contract.md) | `docs/getting-started/linux/`, `docs/getting-started/windows/` | `just check-links` |
| `TASK-012` | Phase 4: Multi-Viewport & WCAG A11y Suite | COMPLETED | [Screenshot A11y Contract](condom/layer-2/001/004/task-006-screenshot-a11y-suite.contract.md) | `screenshot_routes.py`, `accessibility-report.md`, specimens | `just screenshot` (39 specimens) |
| `TASK-013` | Phase 5: Extended Domain Entities & Syllabus Release | PLANNED | `docs/dev/specifications/contracts/domain.contract.md` | Additional schema migrations and domain entities | Domain integration test suite |
| `TASK-014` | Phase 5: Core Service Portability | COMPLETED | `docs/SPECIFICATION.md` | Feature flags, reservations, audit, deposit, and stock services | PHP 8.3 lint / fresh SQLite migration suite |
| `TASK-015` | Phase 5: Tenant Restaurant ERP Operations | COMPLETED | `src/app/Controllers/AdminController.php` | Dining zones, tables, reservation intake, and admin views | PHP 8.3 lint / canonical route suite |
| `TASK-016` | Phase 5: Customer Restaurant Portal | COMPLETED | `src/app/Controllers/CustomerController.php` | Active restaurant listing, menu detail, and CSRF-protected booking | PHP 8.3 lint / canonical route suite |
| `TASK-017` | Phase 6: Restaurant Client ERP Suite (Worker A) | COMPLETED | `docs/SPECIFICATION.md` Section 2.2.B & 4 | Full suite: DiningZones, DiningTables, Reservation, Waitlist, MenuItem, StaffRBAC, Analytics, ReviewModeration, Restaurant | `just test-integration` (29/29 pass) |

---

## 2. Invariant Tracking & System Health
- **Git Safety Protocol**: Explicit-path staging strictly enforced (`GIT_PAGER=cat`, no `git add .`).
- **Isolation Guarantee**: `docs/dev/ai/condom/` confirmed in `.gitignore` and omitted from commit trees.
- **Documentation Triad**: WHY (`architecture.md`), WHAT IS (`tasks.md` / `contracts/`), and WHEN (`CHANGELOG.md`) updated synchronously.
- **Circuit Breaker**: Armed at 2 consecutive command timeouts.

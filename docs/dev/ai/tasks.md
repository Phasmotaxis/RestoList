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
| `TASK-007` | Phase 5: Extended Domain Entities & Stress Drills | PLANNED | `docs/dev/specifications/contracts/domain.contract.md` | Additional schema migrations and entities | MySQL failure & SQLite fallback drill |

---

## 2. Invariant Tracking & System Health
- **Git Safety Protocol**: Explicit-path staging strictly enforced (`GIT_PAGER=cat`, no `git add .`).
- **Isolation Guarantee**: `docs/dev/ai/condom/` confirmed in `.gitignore` and omitted from commit trees.
- **Documentation Triad**: WHY (`architecture.md`), WHAT IS (`tasks.md` / `contracts/`), and WHEN (`CHANGELOG.md`) updated synchronously.
- **Circuit Breaker**: Armed at 2 consecutive command timeouts.

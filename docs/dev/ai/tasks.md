# Active System Tasks & Contract Ledger (WHAT IS)

- **Root Navigation**: [Project Overview](../../../README.md)
- **Architecture Context**: [Architecture Decision Records](../specifications/architecture.md)
- **Change History**: [Changelog](../../CHANGELOG.md)
- **Operational Guide**: [Getting Started Runbook](../../GETTING-STARTED.md)

---

## 1. Master Task Ledger

| Task ID | Domain / Phase | Status | Subsystem Contract | Target Implementation | Test Suite |
|---|---|---|---|---|---|
| `TASK-001` | Phase 1: Repo Scaffolding | COMPLETED | [Scaffolding Contract](condom/layer-2/001/001/task-001-scaffolding.contract.md) | `README.md`, `.agents/rules/`, `.gitignore` | `just check-links` / File verification |
| `TASK-002` | Phase 1: Docker Foundation | IN_PROGRESS | [Docker Contract](condom/layer-2/001/001/task-002-docker.contract.md) | `docker-compose.yaml`, `docker/php/Dockerfile` | `just test` / `docker compose config` |
| `TASK-003` | Phase 2: Error Kernel | PLANNED | `docs/dev/specifications/contracts/error.contract.md` | `src/app/Core/ErrorHandler.php` | `dev/scripts/sh/test-panel-sabotage.sh` |
| `TASK-004` | Phase 2: Dual DB Engine | PLANNED | [Database Contract](../specifications/contracts/database.contract.md) | `src/app/Core/Database.php` | `dev/scripts/py/migrate.py` |
| `TASK-005` | Phase 3: Bitmask RBAC | PLANNED | [RBAC Contract](../specifications/contracts/rbac.contract.md) | `src/app/Middleware/RoleMiddleware.php` | Test harness suite |
| `TASK-006` | Phase 4: Clean-Room UI | PLANNED | `docs/dev/specifications/contracts/ui.contract.md` | `src/public/assets/css/style.css` | W3C / WCAG AA audit |

---

## 2. Invariant Tracking & System Health
- **Git Safety Protocol**: Explicit-path staging strictly enforced (`GIT_PAGER=cat`, no `git add .`).
- **Isolation Guarantee**: `docs/dev/ai/condom/` confirmed in `.gitignore` and omitted from commit trees.
- **Documentation Triad**: WHY (`architecture.md`), WHAT IS (`tasks.md` / `contracts/`), and WHEN (`CHANGELOG.md`) updated synchronously.

# Rules Reconciliation & Governance Audit

- **Root Navigation**: [Project Overview](../../README.md)
- **Agent Governance**: [Root Agent Rules](../../AGENTS.md)
- **Architecture Baseline**: [Architecture Decision Records](../dev/specifications/architecture.md)
- **Active Task Ledger**: [Task Ledger](../dev/ai/tasks.md)
- **Changelog Tracker**: [Project Changelog](../CHANGELOG.md)

---

## 1. Executive Summary
This document records the synchronization and reconciliation between the operator's personal canonical context rules and the sanitized, multi-developer team repository rules established for the Enterprise Information Management System (EIMS).

---

## 2. Rule Reconciliation Matrix

| Personal Rule Domain | Repository Status | Reconciliation / Sanitization Decision |
|---|---|---|
| Input safety (no direct mouse/keyboard) | Enforced via [AGENTS.md](../../AGENTS.md) | Universal non-interactive shell rule applies to all agents; headless execution mode enforced. |
| Formatting & Hygiene (no word wrapping) | Enforced via [.markdownlint.yaml](../../.markdownlint.yaml) | `MD013` disabled across repository; absolute no-word-wrapping policy preserved. |
| Git Safety (explicit path staging only) | Enforced via [AGENTS.md](../../AGENTS.md) | Strictly forbids `git add .`, `-A`, or `-a`. Explicit path staging mandated for all collaborators. |
| Non-interactive commands only | Enforced via [AGENTS.md](../../AGENTS.md) | `GIT_PAGER=cat`, `CI=true`, no `-i` flags. Interactive tools strictly rejected. |
| Terminal Circuit Breaker | Enforced via [AGENTS.md](../../AGENTS.md) | 2-consecutive failure circuit breaker halts execution and requires operator escalation. |
| Documentation Triad | Enforced via [AGENTS.md](../../AGENTS.md) | Triad maintained in lockstep: `architecture.md` (WHY), `tasks.md` (WHAT IS), `CHANGELOG.md` (WHEN). |
| Markdown Web Invariant | Enforced via [justfile](../../justfile) | `just check-links` verifies bidirectional relative links across all documentation files. |
| Zero Ticket Pollution | Enforced via [AGENTS.md](../../AGENTS.md) | Ticket keys forbidden in source code, comments, and commit messages; restricted to markdown ledgers. |
| Strict Typing (`declare(strict_types=1);`) | Enforced in all PHP source files | PHP 8.3 strict types enforced on every application script; zero exceptions. |
| Framework Minimization (Clean-Room) | Enforced across architecture | Pure vanilla PHP, CSS Custom Properties, and HTML5; zero external framework dependencies. |
| Dual-Engine Database Persistence | Enforced in `App\Core\Database` | Primary MySQL 8.0 with automatic dynamic failover to local SQLite3 for air-gapped resilience. |
| Error Interception Kernel | Enforced in `App\Core\ErrorHandler` | Catches runtime errors, uncaught throwables, and fatal shutdowns, rendering visual diagnostics. |
| Personal Credentials / Private Paths | Sanitized & Excluded | All local personal paths and private configuration stripped from tracked repository assets. |
| LaTeX Compilation Standards | Enforced in `Paper/` | XeLaTeX via `latexmk -xelatex -interaction=nonstopmode` used exclusively for academic deliverables. |
| Offline Container Pre-Caching | Enforced in `dev/scripts/sh/pre-cache.sh` | Docker base images archived to `.tar` for air-gapped venue evaluations. |
| Task Contract Specification | Enforced in `docs/dev/ai/condom/layer-2/` | Directed Acyclic Graph (DAG) contracts emitted before agent delegation. |

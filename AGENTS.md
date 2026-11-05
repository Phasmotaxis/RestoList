# Agent Instructions: Enterprise Information Management System (EIMS)

- **System Overview**: [Project Overview](README.md)
- **Architecture Baseline (WHY)**: [Architecture Decision Records](docs/dev/specifications/architecture.md)
- **Active Task Ledger (WHAT IS)**: [Subsystem Tasks](docs/dev/ai/tasks.md)
- **Release History (WHEN)**: [Project Changelog](docs/CHANGELOG.md)
- **Operations & Runbooks**: [Getting Started Runbook](docs/GETTING-STARTED.md)

---

## 1. Orchestration Model & Task Execution
1. **DAG Task Routing**: All complex operations are decomposed into bounded, typed sub-task contracts emitted to `docs/dev/ai/condom/layer-2/` prior to execution.
2. **Empirical Verification**: Never assert a task is complete based on self-reports. Validate files, syntax (`php -l`), container exit codes, and schema migrations empirically before marking tasks complete.
3. **No Ticket Pollution**: Do not inject ticket numbers or issue tracking keys into source code, comments, filenames, or commit messages. Keep ticket references exclusively in markdown registries.

---

## 2. Code & Engineering Standards
1. **Strict Type Safety**: Every PHP source file must begin with `declare(strict_types=1);`.
2. **Zero Framework Dependencies**: Pure clean-room vanilla PHP 8.3, HTML5 semantic structure, and CSS3 custom properties. Zero runtime npm packages or external backend frameworks.
3. **Dual Database Resilience**: Database operations abstract across MySQL 8.0 Primary and local SQLite3 Fallback via `App\Core\Database`.
4. **Defense Kernel**: All runtime exceptions and parse disruptions must be intercepted and rendered through `App\Core\ErrorHandler`.

---

## 3. Shell & Git Execution Guards
1. **Non-Interactive Shell Execution**:
   - Always run in non-interactive mode (`GIT_PAGER=cat`, `CI=true`).
   - Never invoke commands requiring interactive TTY confirmation (`-i`, `git rebase -i`, pagers).
2. **Explicit-Path Git Staging**:
   - Strictly forbid bulk staging (`git add .`, `git add -A`, `git commit -a`).
   - Stage each target file explicitly by exact relative path.
3. **Conventional Commits**:
   - Format messages as `type(scope): imperative summary`.
4. **Terminal Circuit Breaker**:
   - If any command hangs or times out twice consecutively, halt execution immediately, capture diagnostic state, and escalate to the operator.

---

## 4. Documentation Triad Invariant
Maintain the documentation triad in lockstep with all architectural and feature modifications:
- **WHY**: `docs/dev/specifications/architecture.md` (ADRs and design trade-offs)
- **WHAT IS**: `docs/dev/ai/tasks.md` and `docs/dev/specifications/contracts/` (active specifications)
- **WHEN**: `docs/CHANGELOG.md` (strict Keep a Changelog v1.1.0 & SemVer v2.0.0 compliance)

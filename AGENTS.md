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

---

## Codified Project Philosophies (Adapted from ITEC50 Practical)

The following 10 master engineering invariants are derived from the ITEC50 Practical codebase and govern all development in this repository:

### 1. Cartesian Zero-Trust Verification ("Unless You See It, Don't Believe It")
Every agent, orchestrator, and collaborator must treat all prior completion claims, handoff tables, commit messages, test counts, and specification checkboxes as **UNVERIFIED / FALSE** until empirically reproduced and observed firsthand via live commands, file reads, AST/test execution (`exit code 0`), or runtime probes. Never assert completion based on self-reports or prior handoff summaries.

### 2. Clean-Room Implementation & Zero External Frameworks (ADR-001)
Maintain 100% clean-room vanilla PHP 8.3 with `declare(strict_types=1);` and zero external backend frameworks (no Laravel, no Symfony). Avoid external frontend frameworks (no React, no Vue, no Tailwind). Implement request routing, dependency injection, template rendering, and database abstraction natively.

### 3. Dual-Driver Relational Resilience & Offline Failover (ADR-002)
Abstract all database persistence behind `App\Core\Database` supporting both primary MySQL 8.0 and offline SQLite3 fallback. If MySQL becomes unreachable or crashes during evaluation, the runtime must switch to `storage/fallback.sqlite` without human intervention. All queries and migrations must adhere to ANSI-SQL standards.

### 4. Zero Inline Styles & Strict Content-Security-Policy (ADR-004, ADR-008)
Enforce zero inline `style="..."` attributes across all view templates. Style interfaces using semantic CSS3 custom properties, CSS Grid `auto-fill` with `minmax()`, and CSS `mask-image` SVG iconography. Maintain strict CSP headers: `default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none';`.

### 5. Pessimistic UI Synchronization (Client-Server Reality Gap)
Reject optimistic UI updates. Client interfaces must never mutate DOM elements or remove cards until the server returns an explicit HTTP 2xx confirmation. The database is the single source of truth; DOM state reflects verified persistence only.

### 6. Time-Slot Discretization & Driver-Aware Concurrency Control (ADR-010)
Snap all reservation and scheduling timestamps to discrete 15-minute intervals (`:00`, `:15`, `:30`, `:45`). Prevent race-condition double bookings using a UNIQUE constraint on `reservation_slots (org_id, table_id, slot_start_utc)`. In `TableAllocator`, apply driver-aware locking: `SELECT ... FOR UPDATE` on MySQL, and `BEGIN IMMEDIATE` on SQLite3.

### 7. Unified Authentication & Zero Auth Salad
Route all incoming requests through centralized middleware (`CsrfMiddleware`, `AuthMiddleware`, `RoleMiddleware`). Hash passwords with memory-hard algorithms (`PASSWORD_BCRYPT` or `PASSWORD_ARGON2ID`). Verify all hashes, tokens, and signatures with `hash_equals()`. Enforce `session_regenerate_id(true)` upon login. Return uniform error messages to prevent username enumeration.

### 8. Air-Gapped Self-Sufficiency & CDN Independence (ADR-005)
Zero runtime dependencies on remote CDNs. All fonts, styles, scripts, and SVG assets must be served locally from `src/public/assets/`. Container images and deployment packages must be exportable for offline installation in isolated evaluation environments.

### 9. Task Centralization & Headless Non-Interactive Defaults
All development, testing, migration, and seeding workflows must be centralized in the repository `justfile`. All shell executions must be non-interactive (`GIT_PAGER=cat`, `CI=true`, no `-i` flags). Enforce explicit-path Git staging (`git add <file>`) and terminal circuit breaking after two consecutive command timeouts.

### 10. Adversarial Red-Team QA & 100% Syntax Pre-Flight Validation
Validate every PHP file with `php -l` before marking tasks complete. Pair every positive assertion test with an adversarial negative counterpart asserting exact HTTP error status codes. Regularly execute chaos scripts (`test-failover-chaos.sh`) and sabotage simulations (`test-panel-sabotage.sh`) to verify error handling and resilience under failure conditions.

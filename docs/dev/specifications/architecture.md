# Architecture Decision Records & System Architecture (WHY)

- **Root Navigation**: [Project Overview](../../../README.md)
- **Operational Guide**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Active Task State**: [Subsystem Task Ledger](../ai/tasks.md)
- **Change History**: [Changelog](../../CHANGELOG.md)
- **Contracts**:
  - [Database Contract](contracts/database.contract.md)
  - [RBAC Contract](contracts/rbac.contract.md)

---

## 1. System Mission & Context
The Enterprise Information Management System (EIMS) provides a secure multi-tenant operational data backbone. Designed to satisfy rigorous academic evaluation and defense panels, the system demonstrates computer science principles (deterministic state management, mathematical RBAC abstraction, unified exception routing) over production-grade IT infrastructure (isolated container networks, dual-driver database resilience, and air-gapped recovery).

---

## 2. Architectural Decision Records (ADRs)

### ADR-001: Native PHP 8.3 with Strict Typing
- **Status**: Accepted
- **Context**: The project requirement mandates standard web languages (PHP, HTML, CSS, SQL). Modern PHP 8.3 introduces robust type systems, union types, and enhanced error classes (`ParseError`, `TypeError`, `ValueError`).
- **Decision**: Enforce `declare(strict_types=1);` across all application files. Avoid heavy external web frameworks (e.g., Laravel, Symfony) to maintain absolute visibility into internal request pipelines during live code sabotage defense.
- **Consequences**:
  - Positive: Rapid cold starts, negligible memory footprint, 100% auditable source code during oral defense.
  - Negative: Routing, dependency injection, and request dispatching must be implemented clean-room without third-party abstraction packages.

### ADR-002: Dual-Driver Relational Data Abstraction (MySQL + SQLite Fallback)
- **Status**: Accepted
- **Context**: Evaluation environments frequently suffer from external MySQL daemon crashes, port conflicts, or network misconfigurations.
- **Decision**: Abstract database operations behind a unified PDO wrapper (`src/app/Core/Database.php`) supporting both MySQL 8.0 and SQLite3. In the event of primary database connection failure or air-gapped single-node testing, the runtime can seamlessly fall back to a local SQLite database file.
- **Contract Reference**: [Database Contract](contracts/database.contract.md)
- **Consequences**:
  - Positive: Zero-downtime evaluation guarantees. The panel cannot break the demonstration by stopping the MySQL service.
  - Negative: SQL migrations and queries must remain ANSI-SQL compliant, avoiding engine-specific dialetic locks.

### ADR-003: Visual Diagnostic Kernel & Sabotage Interception
- **Status**: Accepted
- **Context**: The defense panel tests candidate mastery by deliberately deleting blocks of code or introducing syntax anomalies. Default PHP execution outputs unformatted fatal error traces or blank 500 white-screens.
- **Decision**: Implement a centralized `ErrorHandler` that hooks `set_error_handler`, `set_exception_handler`, and `register_shutdown_function`. The handler captures any `Throwable`, inspects output buffers, and renders an immediate diagnostic screen pinpointing the file, line number, stack frame, and potential remediation suggestions.
- **Consequences**:
  - Positive: Instant transparency during live sabotage. Reassures examiners that the team understands precise runtime execution state.
  - Negative: Error handlers must remain self-contained and avoid throwing subsequent unhandled exceptions within the handler itself.

### ADR-004: Clean-Room Semantic CSS3 Design System
- **Status**: Accepted
- **Context**: External CSS frameworks (Tailwind, Bootstrap) add thousands of generated classes and external build dependencies that obfuscate styling mechanics.
- **Decision**: Implement a clean-room CSS architecture relying exclusively on CSS Custom Properties (variables), semantic HTML5 tags, flexbox, and grid layouts.
- **Consequences**:
  - Positive: Zero build toolchain overhead for frontend assets, high visual contrast meeting WCAG AA standards, clean source code.
  - Negative: Responsive grid breakpoints and UI components must be manually styled and maintained.

### ADR-005: Containerization & Air-Gapped Pre-Caching
- **Status**: Accepted
- **Context**: Defense venues may have restricted, firewalled, or completely offline local networks.
- **Decision**: Package the entire stack into Docker Compose services (`nginx:1.25-alpine`, `php:8.3-fpm-alpine`, `mysql:8.0`). Provide a pre-caching utility script (`dev/scripts/sh/pre-cache.sh`) that exports container images into compressed tarballs for rapid offline import.
- **Consequences**:
  - Positive: Guarantees exact runtime reproduction across macOS, Linux, and Windows WSL2 without host tool installation.
  - Negative: Requires Docker engine installed on target evaluation hardware.

### ADR-006: Super Admin Global Deployment Governance & Multi-Tenant RBAC
- **Status**: Accepted
- **Context**: The enterprise architecture requires global administrative governance across distributed tenant organizations, while tenant admins must remain strictly partitioned to prevent data leakage.
- **Decision**: Expand `SuperAdmin` authority to global cloud deployment scope. `SuperAdmin` has unrestricted access to list, audit, and provision tenant organizations, audit organization administrators, and inspect all users across tenants. `Admin` (Tenant Admin) remains mathematically constrained by `tenant_id` boundaries.
- **Decision**: Keep restaurant-domain migration records scoped by `org_id` while retaining `tenant_id` on the legacy identity and audit tables; services must bind the supplied tenant identifier consistently and use portable SQL expressions for both supported PDO drivers.
- **Contract Reference**: [RBAC Contract](contracts/rbac.contract.md)
- **Consequences**:
  - Positive: Clear separation of operational concerns between platform-level operators and tenant administrators. Single audit plane for institutional governance.
  - Negative: Super Admin endpoints must strictly enforce bitmask checks to prevent privilege escalation from tenant-level administrators.

### ADR-007: Canonical 13-Route Dispatching, Automated Testing, and XeLaTeX Defense Monograph
- **Status**: Accepted
- **Context**: Comprehensive panel evaluation requires deterministic verification of all functional entrypoints and publication-grade academic defense documentation without framework sprawl.
- **Decision**: Implement a canonical 13-route inventory covering public, superadmin, admin, user, and diagnostic error endpoints in `src/public/index.php`. Support dual web and JSON response negotiation. Author a modular XeLaTeX capstone monograph under `Paper/` compiled via `latexmk -xelatex`, alongside automated route testing in `dev/scripts/py/screenshot_routes.py`.
- **Consequences**:
  - Positive: Complete empirical test coverage, deterministic route verification, and formal academic documentation adhering to university capstone standards.
  - Negative: Requires maintaining LaTeX document structure in lockstep with codebase evolution.

### ADR-008: Accessible Modular Design System (Zero Inline CSS & Lyra Theme)
- **Status**: Accepted
- **Context**: Mixing inline `style="..."` attributes within PHP views causes CSS specificity leakage, impedes system-wide theme toggling, and fails WCAG AA contrast standards.
- **Decision**: Refactor 100% of inline styles to modular CSS token architecture (`base.css`, `themes/default.css`, `themes/lyra.css`). Extract high-contrast tokens from `whinee/piiiiink.json` with >= 4.5:1 text contrast and accessible focus rings. Implement zero-dep vanilla theme engine persisting selections across page reloads.
- **Consequences**:
  - Positive: Strict WCAG AA compliance, zero visual regressions, instant theme extensibility in under 5 minutes.
  - Negative: Requires strict adherence to semantic CSS utility classes across all new view partials.

### ADR-009: High-Load Transactional Seeding and Dynamic Failover Verification
- **Status**: Accepted
- **Context**: Faculty evaluation demands empirical proof of relational indexing and resilience against container termination during live demonstration.
- **Decision**: Implement chunked multi-row transactional seeder (`seed_stress.py`) capable of generating 100,000+ records with precomputed bcrypt hashes, automated query benchmark runner (`benchmark_queries.py`) asserting < 25ms SLA, and an automated container chaos engineering harness (`test-failover-chaos.sh`).
- **Consequences**:
  - Positive: Empirically verified sub-millisecond query performance on indexed tables; guaranteed survival and write persistence when MySQL container is killed.
  - Negative: High-volume stress seeding requires several gigabytes of ephemeral disk allocation during bulk runs.

### ADR-010: Userspace 3 Customer Portal & Privacy-Preserving Slot Architecture
- **Status**: Accepted
- **Context**: The restaurant ERP platform requires public self-service diner engagement (Userspace 3) covering discovery, real-time availability, table bookings, live menu sync, remote waitlist, diner reviews, and feature-gated deposits and loyalty programs without leaking internal floor plan coordinates or physical table layouts.
- **Decision**: Implement modular Customer Portal controllers (`DiscoveryController`, `SlotAvailabilityController`, `BookingFlowController`, `MenuSyncController`, `WaitlistPortalController`, `ReviewSubmissionController`, `DepositPortalController`, `LoyaltyPortalController`, `ConciergeController`, `CustomerController`) adhering to strict native PHP 8.3 and clean-room principles. Extend `Request` and `Router` with parameterized `:param` dynamic matching. Protect table allocation through atomic transactional locking in `TableAllocator` with UNIQUE constraint on `reservation_slots(org_id, table_id, slot_start_utc)` to prevent race-condition double-booking. Enforce feature flag gates via `FeatureFlagService::isEnabled` for deposits and loyalty. Filter reviews through anti-spam heuristic link detection.
- **Consequences**:
  - Positive: Complete isolation between internal restaurant operations and public diner userspace; privacy preservation of floor plan geometry; strict concurrency safety against double-booking; zero external dependencies.
  - Negative: Requires maintaining dual web and JSON response contracts across all public entrypoints.

## 3. Failure Mode Analysis & Defenses

| Failure Scenario | Root Cause | System Defense |
|---|---|---|
| Panel terminates MySQL container | Port conflict or manual sabotage | `Database.php` catches PDO exception and switches connection to local SQLite fallback database |
| Panel deletes semicolon or statement in Controller | Syntax error / `ParseError` | `register_shutdown_function` catches fatal error via `error_get_last()` and renders visual diagnostic terminal |
| Unauthorized role accesses administrative endpoint | Tampered request parameters | `RoleMiddleware.php` validates bitmask permissions against session state, aborting with 403 Forbidden |
| Loss of internet access during venue deployment | Offline venue network | Docker images pre-cached via `dev/scripts/sh/pre-cache.sh` loadable locally without registry access |

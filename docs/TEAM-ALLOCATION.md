# DICT24 Information Management Finals: Team Allocation Matrix

**Project**: Multi-Tenant Restaurant ERP & Reservation Platform (EIMS)  
**Academic Term**: 4th year, 1st semester 2026-27, Cavite State University  
**Final Assessment**: DICT24 Information Management  
**Completion Target**: 2026-12-15  

---

## Executive Summary

This document maps team members to subsystems, specifies ownership boundaries, and establishes a coordination protocol across three parallel execution waves (Wave 1 core infrastructure, Wave 2 parallel squad development, Wave 3 integration & hardening).

**Orchestrator Role**: Lyra (`whinee`) — Master Orchestrator, Core Schema, SuperAdmin Module  
**Total Team**: 7 developers across 3 squads  
**Execution Model**: Hybrid (Lyra orchestrates from main branch; Worker 1 & Worker 2 develop in isolated worktrees and merge upstream)

---

## 1. Role Definitions

### 1.1 Master Orchestrator (Lyra / `whinee`)
- **Scope**: Wave 0 hardening, Wave 1 core schema & services, Wave 2 supervision, Wave 3 integration
- **Responsibilities**:
  - Lock consolidated specification and team allocation
  - Provision database schema and core services (`FeatureFlagService`, `AuditLogger`, `TableAllocator`, `DepositLedgerService`, `StockLedgerService`)
  - Implement SuperAdmin userspace (tenant provisioning, feature-flag toggles, invite generation, audit log viewer)
  - Coordinate parallel squad workers via git worktrees and test gates
  - Verify `just test` passes green before releasing Wave 1 to workers
  - Merge `feat/client-erp` and `feat/customer-portal` branches cleanly in Wave 3
  - Final integration testing and concurrency stress verification

### 1.2 Client-Facing Restaurant ERP Squad (Worker 1)
**Lead**: Rian (`orewayani`)  
**Members**: Ares (`arezione`), Beatrice (`eyaaab`), Shiella (`ella-shiella`)

**Scope**: Restaurant operations, staff management, analytics, optional floor-plan & inventory modules

**Deliverables**:
- Live table status board (`available`, `reserved`, `occupied`, `cleaning`) with validated state transitions
- Reservation CRUD, walk-in waitlist, automated no-show cancellation windows
- Instant waitlist backfill promotion (pop queue, assign table, transition reservation)
- Digital menu CRUD with centavo pricing, dietary/allergen tags, instant 86-list toggling (`is_available`)
- Granular staff RBAC (`Admin/Manager`, `FrontOfHouse`, `Kitchen`) gating
- Operational analytics dashboard (turnover rate, peak hours, no-show %, top items)
- Customer review response dashboard
- **[FLAG-GATED]** CSP-safe 12×8 CSS Grid floor-plan builder (`enable_floor_plan`)
- **[FLAG-GATED]** Inventory & recipe BOM management (`enable_inventory_erp`)
- Unit & integration tests for all routes and state machines
- Zero inline `style=` attributes; all styling via CSS utility classes

**Git Branch**: `feat/client-erp` (branched from Wave 1 completion)  
**Test Gate**: `php -l` + `just test` passes before merge to integration branch

---

### 1.3 Customer-Facing Diner Portal Squad (Worker 2)
**Lead**: Zy (`cesszi`)  
**Members**: Xy (`xxzaazxx`), Chie (`adoboenthusiast`)

**Scope**: Marketplace discovery, reservation booking, self-service management, waitlist, chatbot, reviews, optional deposit/loyalty

**Deliverables**:
- Multi-restaurant marketplace search (org name, cuisine, dietary support, zone filtering)
- Restaurant detail page with availability calendar synced to `TableAllocator`
- Interactive digital menu with live `is_available` status and allergen/dietary tags
- Slot-locked table booking flow (party size, preferred zone, structured allergy notes)
- Signed self-service booking links (confirm, modify, cancel without re-login)
- Remote walk-in waitlist join with live queue position
- Simulated `(Mock)` SMS/in-app alerts on queue progression
- Deterministic SQL-backed concierge chatbot:
  - Operating hours lookup
  - Location & parking info
  - Allergen-safe dish search
  - Reservation status by booking code
- Post-dining ratings/reviews + restaurant response flow
- **[FLAG-GATED]** Centavo deposit pre-payment checkout (`enable_deposits`)
- **[FLAG-GATED]** Visit-based loyalty points ledger (`enable_loyalty`)
- Unit & integration tests for all booking flows, chatbot, and feature-flag scenarios
- Zero inline `style=` attributes; responsive design via CSS Grid/Flexbox classes

**Git Branch**: `feat/customer-portal` (branched from Wave 1 completion)  
**Test Gate**: `php -l` + `just test` passes before merge to integration branch

---

## 2. Subsystem Ownership & Boundaries

| Subsystem | Owner(s) | Scope | Dependencies |
|-----------|----------|-------|--------------|
| **Core Schema** | Lyra | `organizations`, `org_feature_flags`, `org_invites`, `users`, `sessions`, `audit_logs`, `login_attempts`, `dining_zones`, `dining_tables`, `reservations`, `reservation_tables`, `reservation_slots`, `menu_items`, `reviews`, `deposit_ledger`, `loyalty_ledger`, optional `ingredients`, `menu_item_recipes`, `purchase_orders`, `stock_movements` | None (foundational) |
| **FeatureFlagService** | Lyra | Per-tenant flag toggles, cascading reads | Database schema |
| **AuditLogger** | Lyra | Append-only structured audit entries, cross-tenant scoped viewer | Database schema |
| **TableAllocator** | Lyra | Transactional double-booking prevention, slot bucketing, multi-table locking | Database schema, LoginThrottle |
| **DepositLedgerService** | Lyra | Centavo holds/forfeits/refunds, append-only ledger | Database schema |
| **StockLedgerService** | Lyra | Ingredient movement tracking, on-hand calculation | Database schema (optional, feature-gated) |
| **SuperAdmin Userspace** | Lyra | Tenant provisioning, feature-flag UI, invite links, audit log viewer, session revocation | All core services |
| **Restaurant ERP Routes** | Worker 1 (Rian lead) | Table board, reservations, menu, staff, analytics, reviews | Core services, SuperAdmin |
| **FrontOfHouse Routes** | Worker 1 (Rian lead) | Quick-access dashboard, table status updates, check-in flow | Core services |
| **Kitchen Routes** | Worker 1 (Rian lead) | Prep queue, mark-ready | Core services |
| **Floor Plan Builder** | Worker 1 (Flag: `enable_floor_plan`) | Drag-drop 12×8 grid, CSS class generation | Core services, FeatureFlagService |
| **Inventory ERP** | Worker 1 (Flag: `enable_inventory_erp`) | Ingredient/recipe CRUD, stock tracking, purchase orders | Core services, StockLedgerService, FeatureFlagService |
| **Customer Portal Routes** | Worker 2 (Zy lead) | Marketplace, search, org detail, menu view | Core services, TableAllocator |
| **Booking Flow** | Worker 2 (Zy lead) | Slot-locked form, form submission, signed link generation | Core services, TableAllocator |
| **Self-Service Links** | Worker 2 (Zy lead) | Confirmation, modification, cancellation via signed tokens | Core services, TableAllocator |
| **Waitlist Management** | Worker 2 (Zy lead) | Join remote queue, live position, simulated alerts | Core services |
| **Concierge Chatbot** | Worker 2 (Zy lead) | SQL rule-engine, hours lookup, allergen search, reservation status | Database schema |
| **Reviews & Ratings** | Worker 2 (Zy lead) | Post-dining submission, rating aggregation, restaurant response | Core services, AuditLogger |
| **Deposits** | Worker 2 (Flag: `enable_deposits`) | Checkout hold, ledger recording | Core services, DepositLedgerService, FeatureFlagService |
| **Loyalty Points** | Worker 2 (Flag: `enable_loyalty`) | Visit counting, ledger recording, balance display | Core services, FeatureFlagService |
| **CSS Utility Classes** | Both squads | Shared `gc-*`, `gr-*`, `span-w-*`, `span-h-*`, `status-*`, responsive classes | Design system |
| **Testing** | Both squads + Lyra | Unit, integration, concurrency, security (RED team) | All subsystems |

---

## 3. Execution Waves & Milestones

### Wave 0: Hardening Closure & Spec Lock (Lyra, Main Worktree)
**Timeline**: 2026-11-19 morning  
**Gate**: All RED R4 BROKEN items addressed; `just test` green

- [ ] Scrub `Password123!` and inline `style=` from specimens
- [ ] Default `APP_DEBUG=false` in `.env.example` and `docker-compose.yaml`
- [ ] Fix `LoginThrottle.php`: per-email (5) vs per-IP (10) isolation, anti-enumeration
- [ ] Update `dev/scripts/sh/test-panel-sabotage.sh`: test `APP_DEBUG=false` branch
- [ ] Lock `docs/SPECIFICATION.md` and `docs/TEAM-ALLOCATION.md`
- [ ] Run `just test` → **0 failures** required before Wave 1

### Wave 1: Core Schema & SuperAdmin (Lyra, Main Worktree)
**Timeline**: 2026-11-19 midday → 2026-11-20 morning  
**Gate**: `just test` passes green; all services tested; SuperAdmin userspace complete

- [ ] Idempotent database migrations (MySQL 8 + SQLite compatible)
- [ ] Core PHP services: `FeatureFlagService`, `AuditLogger`, `TableAllocator`, `DepositLedgerService`, `StockLedgerService`
- [ ] SuperAdmin routes: tenant provisioning, feature-flag toggles, invite links, audit viewer, session revocation
- [ ] Unit & integration tests for all services
- [ ] `php -l` zero syntax errors
- [ ] Run `just test` → **0 failures** required
- [ ] Tag branch as `wave-1-complete` for worker branching

### Wave 2: Parallel Squad Development (Workers 1 & 2, Isolated Worktrees)
**Timeline**: 2026-11-20 morning → 2026-11-22 evening  
**Gate**: Each squad delivers `php -l` + `just test` passes

#### Worker 1: Client ERP Squad (`feat/client-erp`)
- [ ] All Restaurant ERP routes (admin dashboard, tables, menu, staff, analytics, reviews)
- [ ] All FrontOfHouse + Kitchen routes
- [ ] RBAC middleware gating
- [ ] Floor-plan builder (flag-gated, zero inline styles)
- [ ] Inventory ERP (flag-gated)
- [ ] CSS utility classes (`gc-*`, `gr-*`, `span-w-*`, `span-h-*`, `status-*`)
- [ ] Unit & integration tests
- [ ] `php -l` zero syntax errors
- [ ] `just test` passes

#### Worker 2: Customer Portal Squad (`feat/customer-portal`)
- [ ] Marketplace search & org detail
- [ ] Booking flow + signed self-service links
- [ ] Waitlist join + simulated alerts
- [ ] Concierge chatbot
- [ ] Reviews & ratings
- [ ] Deposits & loyalty (flag-gated)
- [ ] Responsive CSS classes
- [ ] Unit & integration tests
- [ ] `php -l` zero syntax errors
- [ ] `just test` passes

### Wave 3: Integration & Hardening (Lyra, Main Worktree)
**Timeline**: 2026-11-23 morning → 2026-11-24 evening  
**Gate**: End-to-end testing, concurrency stress, final RED team verification

- [ ] Merge `feat/client-erp` cleanly into integration branch
- [ ] Merge `feat/customer-portal` cleanly into integration branch
- [ ] Resolve route/navigation conflicts; preserve single unified UI shell
- [ ] Run `just test` → **0 failures**
- [ ] **Concurrency Stress Tests**:
  - 50+ simultaneous booking attempts on same table/overlapping window
  - Verify zero double-bookings, atomic success/failure
  - Multi-table combinable booking correctness
  - SQLite + MySQL both tested
- [ ] **Cross-Tenant Isolation**: Admin A cannot read/edit Org B data
- [ ] **Feature-Flag Gating**: Disabled features cleanly hidden/return 404
- [ ] **Soft-Archive Guards**: Cannot archive table with active future reservations
- [ ] **Grep Verification**:
  - `grep -r 'style=' src/ docs/specimens/` → 0 matches
  - `grep -r 'Password123' src/ docs/` → 0 matches
  - `php -l src/**/*.php` → 0 errors
- [ ] Seed realistic multi-tenant fixtures
- [ ] Write integration audit report to `docs/dev/ai/audit/dict24-wave3-integration.md`
- [ ] Final RED team verification (authorization matrix, XSS, CSRF, sessions, throttle)
- [ ] **Commit & Tag**: `git tag v1.0.0-finals` on main branch

---

## 4. Git & Branching Strategy

### Main Branch (`main`)
- **Purpose**: Stable, production-ready code
- **Wave 0, 1, 3 commits**: Orchestrator (Lyra) only
- **Protection**: No direct pushes; all changes via Wave-gated commits

### Worker Branches (`feat/client-erp`, `feat/customer-portal`)
- **Branched from**: `main` at end of Wave 1
- **Isolation**: Separate git worktrees (`orca worktree new-top-level`)
- **Merge Strategy**: Squash-and-merge or rebase into main during Wave 3
- **Conflict Resolution**: Orchestrator (Lyra) merges; squads notify of conflicts

### Release Tags
- `wave-1-complete` — End of Wave 1; workers branch from here
- `v1.0.0-finals` — Final release candidate (end of Wave 3)

---

## 5. Communication & Escalation

### Daily Standup
- **Time**: 08:00 UTC (or agreed async via CHANGELOG updates)
- **Format**: 1-line status per squad in `docs/dev/ai/tasks.md`
- **Blockers**: Escalate immediately to Orchestrator (Lyra)

### Weekly Sync (if needed)
- **Purpose**: Spec clarifications, cross-squad dependency resolution
- **Participants**: Lyra (orchestrator) + squad leads

### Escalation Path
1. **Unclear Spec**: Post in `docs/dev/ai/audit/dict24-questions.md` with tag `[QUESTION]`
2. **Blocker**: Update `docs/dev/ai/tasks.md` with `[BLOCKED]` tag; escalate to Lyra
3. **Merge Conflict**: Notify Lyra; do not attempt manual resolution in worktree
4. **Test Failure**: Attach `just test` output to task ledger; halt further commits pending fix

---

## 6. Quality & Acceptance Criteria

### Code Review Gates (Wave 2 → Wave 3)
Before merge to main:
1. `php -l` on all new/modified `.php` files → 0 syntax errors
2. `grep -r 'style=' src/ docs/specimens/` → 0 inline style matches
3. `just test` → exit code 0, all checks pass
4. Cross-team review: Orchestrator (Lyra) approves both squads' PRs

### Test Coverage Minimums
- **Unit Tests**: All services (`FeatureFlagService`, `AuditLogger`, `TableAllocator`, etc.) + validators
- **Integration Tests**: Multi-tenant isolation, double-booking prevention, feature-flag gating, soft-archive guards
- **Concurrency Tests**: 50+ simultaneous booking attempts on same table/overlapping window
- **Security Tests**: Authorization matrix, XSS (stored/reflected), CSRF, session invalidation, LoginThrottle

### Performance Targets
- **Booking creation**: < 100ms (including TableAllocator transactional lock)
- **Menu retrieval**: < 50ms per org + 1000-item menu
- **Analytics query**: < 500ms for 90-day window
- **Audit log pagination**: < 100ms per 100-entry page

---

## 7. Known Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Parallel squad merge conflicts | Integration delay | Early CSS/routing class standardization; Lyra owns final merge |
| Double-booking race condition | Data integrity failure | Two-layer lock + 15-min slot bucketing; stress-tested at 50+ concurrent |
| Feature-flag partial implementation | Broken UX when disabled | All flag checks tested; disabled features return 404 or hide cleanly |
| LoginThrottle per-IP collateral lockout | User DOS | Separate threshold (10) vs email (5); identical HTTP 429 response |
| Cross-tenant data leak | Security breach | RBAC middleware + scoped queries + audit trail; RED team verifies |
| SQLite/MySQL schema drift | Test-prod mismatch | Idempotent migrations; both tested on every commit |

---

## 8. Success Criteria (Deliverable)

**By 2026-12-15**, the integrated system must meet ALL of the following:

- [ ] Single unified UI shell adapts per role and feature flags
- [ ] SuperAdmin: tenant provisioning, feature-toggle UI, audit log viewer, session revocation
- [ ] Restaurant ERP: table board, reservations, menu, staff RBAC, analytics, reviews, optional floor-plan/inventory
- [ ] Customer Portal: marketplace, booking, self-service mgmt, waitlist, chatbot, reviews, optional deposits/loyalty
- [ ] Zero double-bookings under 50+ concurrent booking attempts
- [ ] Multi-table combinable bookings atomic and correct
- [ ] Cross-tenant data isolation: Admin A cannot read/edit Org B
- [ ] Feature flags cleanly gate optional modules
- [ ] Soft-archive guards prevent archival of tables with active future reservations
- [ ] LoginThrottle per-email (5) and per-IP (10) isolation tested
- [ ] `php -l` passes on all PHP files (0 syntax errors)
- [ ] `grep -r 'style=' src/ docs/specimens/` → 0 matches
- [ ] `grep -r 'Password123' src/ docs/` → 0 matches
- [ ] `just test` passes (0 test failures) on both MySQL 8 and SQLite
- [ ] Concurrency stress test: 50+ simultaneous bookings on same table/overlapping window → 0 double-bookings
- [ ] RED team authorization matrix: 4 roles × 10+ routes verified
- [ ] RED team security: XSS, CSRF, session invalidation, SQL injection protections verified
- [ ] All routes documented in `docs/SPECIFICATION.md` (section 4)
- [ ] Team allocation & responsibilities locked in this document
- [ ] Integration audit report written to `docs/dev/ai/audit/dict24-wave3-integration.md`
- [ ] Final tag: `v1.0.0-finals` on main branch

---

**Document Version**: 1.0  
**Last Updated**: 2026-11-19  
**Approved By**: Lyra (`whinee`)  
**Status**: LOCKED ✓

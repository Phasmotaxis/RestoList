# DICT24 Restaurant ERP: Layer 2 Worker Team Allocation

**Date**: 2026-11-20  
**Phase**: Wave 0.2 Team Allocation  
**Status**: LOCKED for parallel Layer 2 worker dispatch (Wave 2)  
**Revision**: Final  
**Human Teammate Backlog**: [Team Onboarding Guide & Developer Handbook](TEAM-ONBOARDING-GUIDE.md) | [Comprehensive Teammate Task Allocation Matrix (30 Tasks, 6 Roles)](TEAM-ALLOCATION-TASKS.md) | [54-Ticket Atomic Volunteer Task Queue (8 Members)](TEAM-TASK-QUEUE.md)

---

> **Note for Course Evaluators and Student Groups**: This document outlines the automated Layer 2 worker orchestration model for kernel subsystems. For the 4-to-8 human member project delegation backlog covering non-kernel deliverables (Video Production, Windows/XAMPP QA, Cultural Menu Data, User Manuals, Oral Defense Coordination, and Relational Schema Documentation), see **[docs/TEAM-ALLOCATION-TASKS.md](TEAM-ALLOCATION-TASKS.md)**. For the granular 54-ticket atomic Kanban task queue for the 8-member group, see **[docs/TEAM-TASK-QUEUE.md](TEAM-TASK-QUEUE.md)**.

## 1. Executive Summary

Wave 0–1 (Layer 1 Master Orchestrator) establishes the unified specification, core infrastructure (18 migrations, 5 core services), and SuperAdmin userspace. Wave 2 dispatches **two isolated Layer 2 workers** in parallel to build the Restaurant Client ERP and Customer Diner Portal independently, with conflict resolution and merge sequencing managed by Layer 1.

**Execution Model**:
- **Layer 2 Worker A** (`feat/client-erp` branch): Restaurant Admin ERP (dining zones, floor plan, reservations, waitlist, staff RBAC, menu 86-list, analytics)
- **Layer 2 Worker B** (`feat/customer-portal` branch): Customer Diner Portal (discovery, booking, menu sync, reviews, deposits/loyalty)
- Both workers execute in parallel (90 minutes)
- Layer 1 merges both branches to `main` after Wave 2 completion (Wave 3)
- Conflict resolution: Layer 1 arbitrates overlapping controllers or migrations

---

## 2. Layer 2 Worker A: Restaurant Client ERP

**Branch**: `feat/client-erp`  
**Worktree**: Isolated git worktree (auto-created by Orca)  
**Scope**: 60–80 controllers, 80+ integration tests  
**Duration**: 90 minutes (concurrent with Worker B)  
**Deliverable**: Full ERP suite, 0 failing tests

### 2.1 Scope: Dining Zones & Floor Plan

**Controllers**: `DiningZonesController`, `DiningTablesController`

#### DiningZonesController
```php
class DiningZonesController {
    public function index(): void // GET /admin/restaurants/:id/zones
    public function create(): void // GET /admin/restaurants/:id/zones/create
    public function store(): void  // POST /admin/restaurants/:id/zones
    public function edit(): void   // GET /admin/restaurants/:id/zones/:zone_id/edit
    public function update(): void // POST /admin/restaurants/:id/zones/:zone_id
    public function delete(): void // POST /admin/restaurants/:id/zones/:zone_id/delete
}
```

**Features**:
- Create zone: name, description, optional photo
- Edit zone: rename, archive (soft delete via `archived_at`)
- List zones: grid display with table count, occupancy summary
- Audit logging on every state change

#### DiningTablesController
```php
class DiningTablesController {
    public function index(): void  // GET /admin/restaurants/:id/tables
    public function create(): void // GET /admin/restaurants/:id/tables/create
    public function store(): void  // POST /admin/restaurants/:id/tables
    public function edit(): void   // GET /admin/restaurants/:id/tables/:table_id/edit
    public function update(): void // POST /admin/restaurants/:id/tables/:table_id
    public function delete(): void // POST /admin/restaurants/:id/tables/:table_id/delete
    public function getCoordinates(): void // GET /admin/restaurants/:id/tables/:table_id/coords (JSON)
}
```

**Features**:
- Add table: zone, table number, capacity (1–20), combinable flag, coordinates (x, y)
- Edit: move, resize, change capacity
- Delete: archive (soft delete via `is_active=false`)
- Real-time occupancy: integrate with `reservations` table to show status (available, reserved, occupied, cleaning)
- Drag-drop endpoint: POST coordinates for reposition (stub UI; backend stores x/y)

**Tests**:
- Create zone → verify audit log
- Add table to zone → verify foreign key
- Multi-tenant scoping: User in org 1 cannot see org 2's zones
- Occupancy calculation: 3 tables booked, 1 available → display correct counts

### 2.2 Scope: Reservations & TableAllocator

**Controllers**: `ReservationController`

```php
class ReservationController {
    public function index(): void  // GET /admin/restaurants/:id/reservations
    public function create(): void // GET /admin/restaurants/:id/reservations/create
    public function store(): void  // POST /admin/restaurants/:id/reservations
    public function edit(): void   // GET /admin/restaurants/:id/reservations/:id/edit
    public function update(): void // POST /admin/restaurants/:id/reservations/:id
    public function cancel(): void // POST /admin/restaurants/:id/reservations/:id/cancel
    public function confirm(): void // POST /admin/restaurants/:id/reservations/:id/confirm
}
```

**Features**:
- List reservations: filter by date range, status (confirmed, cancelled, no-show), search by guest name/email
- Create: TableAllocator concurrency safety, 15-min boundary snapping, prevent double-booking
- Edit: change table selection, party size, time window (validate no conflicts)
- Cancel: deallocate slots, update status, trigger waitlist backfill
- Confirm: mark as checked-in (start time reached)

**Integration Points**:
- **TableAllocator service**: atomic allocation + deallocation
- **Audit logging**: every state change
- **Waitlist backfill**: when table freed, auto-match first waiting entry
- **Multi-tenant scoping**: filter by org_id and restaurant_id

**Tests**:
- Concurrent booking: two simultaneous POST requests for same table → exactly one succeeds, one gets 409 Conflict
- Double-booking prevention: reserve 19:00–20:30, attempt 19:15–20:00 → rejected
- 15-min snapping: request 19:13 → snaps to 19:00, allocates slots 19:00, 19:15, 19:30, 19:45, 20:00
- Cancellation: mark reservation cancelled, verify `reservation_slots` deleted, slot immediately rebookable
- Multi-table combinable: reserve adjacent tables as one reservation, lock atomically, verify isolation
- Waitlist backfill: cancel 4-top reservation, first waitlist entry (4 people) auto-seated

### 2.3 Scope: Waitlist Management

**Controllers**: `WaitlistController`

```php
class WaitlistController {
    public function index(): void  // GET /admin/restaurants/:id/waitlist
    public function add(): void    // POST /admin/restaurants/:id/waitlist
    public function updatePosition(): void // POST /admin/restaurants/:id/waitlist/:entry_id/position
    public function notify(): void // POST /admin/restaurants/:id/waitlist/:entry_id/notify (email stub)
    public function remove(): void // POST /admin/restaurants/:id/waitlist/:entry_id/remove
}
```

**Features**:
- Add to waitlist: email, party size, zone preference (optional), position auto-calculated (last + 1)
- List: show all entries, position, created_at, status (waiting, seated, cancelled, expired)
- Update position: host can reorder waitlist (e.g., move VIP to top)
- Notify: send "table ready" email (stub—don't actually send, log to audit)
- Backfill algorithm: when table freed, match first waiting entry by party size, auto-create reservation

**Tests**:
- Add entry: verify position = max(position) + 1
- Reorder: change position of entry 2 to 1, verify others shift
- Backfill: cancel 4-top, 4-person waitlist entry auto-promoted to confirmed reservation
- Multi-tenant scoping: verify entries isolated by org_id
- Expiry: entries > 24 hours old marked expired

### 2.4 Scope: Menu 86-List Management

**Controllers**: `MenuItemController` (extends Phase 6)

```php
class MenuItemController {
    public function toggle86(): void // POST /admin/restaurants/:id/menu/:item_id/86
    public function list86(): void   // GET /admin/restaurants/:id/menu/86
}
```

**Features**:
- Mark item as 86 (unavailable): timestamp, actor, reason
- Bulk 86: select multiple items, mark unavailable
- Revert: remove 86 status, make available again
- List 86: show all currently unavailable items, sorted by actor, timestamp
- Real-time sync: Customer Portal menu reflects 86-list immediately

**Integration Points**:
- **Customer Portal**: menu sync respects 86-list (greyed-out or removed)
- **Audit logging**: 86 toggle captured in audit_logs
- **Menu history**: track 86 events for analytics (most-frequently-unavailable items)

**Tests**:
- Mark item 86: verify menu endpoint excludes it
- Revert: verify menu includes it again
- Bulk 86: mark 5 items, verify all 5 unavailable
- Multi-tenant scoping: org 1 86-list invisible to org 2

### 2.5 Scope: Staff RBAC

**Controllers**: `StaffRBACController`

```php
class StaffRBACController {
    public function index(): void    // GET /admin/restaurants/:id/staff
    public function create(): void   // GET /admin/restaurants/:id/staff/create
    public function invite(): void   // POST /admin/restaurants/:id/staff/invite
    public function revokeAccess(): void // POST /admin/restaurants/:id/staff/:user_id/revoke
    public function updateRole(): void // POST /admin/restaurants/:id/staff/:user_id/role
}
```

**Roles** (per restaurant):
- **Manager**: full CRUD (reservations, waitlist, menu, staff, analytics)
- **FrontOfHouse** (Server/Host): create/edit reservations, view waitlist, check-in guests
- **Kitchen**: view reservations filtered to kitchen orders only, view menu, cannot edit
- **Cleaning**: view tables needing cleaning, mark cleaned

**Features**:
- Invite staff: email + role assignment, generate invite token
- Revoke: immediate session termination (via session revocation)
- Update role: manager can reassign role, audit-logged
- Session enforcement: middleware checks role on every request, returns 403 if unauthorized

**Tests**:
- FrontOfHouse cannot create user: attempt → 403
- Kitchen cannot edit reservation: attempt → 403
- Manager CRUD: all operations allowed
- Role change: update user from Server → Manager, verify permissions elevated
- Cross-restaurant scoping: staff at restaurant 1 cannot access restaurant 2 (403)

### 2.6 Scope: Analytics & Reporting

**Controllers**: `AnalyticsController`

```php
class AnalyticsController {
    public function dashboard(): void // GET /admin/restaurants/:id/analytics
    public function turnover(): void  // GET /admin/restaurants/:id/analytics/turnover
    public function peakHours(): void // GET /admin/restaurants/:id/analytics/peak-hours
    public function noShowRate(): void // GET /admin/restaurants/:id/analytics/no-show
    public function topItems(): void  // GET /admin/restaurants/:id/analytics/top-items
    public function revenue(): void   // GET /admin/restaurants/:id/analytics/revenue
}
```

**Features**:
- Turnover %: (minutes seated / table capacity / opening hours) × 100
- Peak hours: hourly distribution of active reservations (heatmap data)
- No-show %: (cancelled + no-show) / total × 100
- Top items: menu items by order frequency (from linked reservations or POS stub)
- Revenue: sum of deposits + meal charges (if linked to ledger)

**Data**:
- Date range filter: last 7 days, last month, custom range
- Dashboard: widgets for all metrics, trend arrows (up/down)
- Drill-down: click metric → detail view with filters

**Tests**:
- Turnover calculation: 4-top × 2 hours / 4 capacity × 10 hours open = 20%
- Peak hours: 19:00–20:00 has 12 active reservations, 18:00–19:00 has 3 → API returns correct counts
- No-show rate: 2 no-shows out of 50 total = 4%
- Revenue: 5 deposits of $50 each = $250 (5000 centavos)

### 2.7 Scope: Review Moderation

**Controllers**: `ReviewModerationController`

```php
class ReviewModerationController {
    public function pending(): void   // GET /admin/restaurants/:id/reviews/pending
    public function approve(): void   // POST /admin/restaurants/:id/reviews/:id/approve
    public function reject(): void    // POST /admin/restaurants/:id/reviews/:id/reject
    public function reply(): void     // POST /admin/restaurants/:id/reviews/:id/reply
}
```

**Features**:
- Pending list: reviews not yet approved, sorted by created_at
- Approve: mark approved, visible on public portal
- Reject: delete or flag as rejected (user can see reason)
- Reply: manager posts response to review (visible on public portal)
- Spam filter: reviews with >1 link or <5 words auto-flagged

**Tests**:
- Spam detection: review with "click here" link → pending
- Approve: review becomes visible in portal
- Reply: manager response appears linked to original review
- Multi-tenant scoping: reviews isolated by org_id

### 2.8 Integration Tests (Worker A)

**File**: `tests/integration/RestaurantERP_Test.php`

**Scope**: 100+ test cases covering:
- All controllers' CRUD operations
- Multi-tenant isolation (org 1 cannot access org 2 data)
- RBAC enforcement (Staff cannot do Admin operations)
- Concurrency: simultaneous bookings
- Audit logging verification
- Input validation (422 responses)
- Error handling (404, 403, 500 scenarios)

**Target**: 90%+ coverage on critical paths (ReservationController, TableAllocator, RoleMiddleware)

---

## 3. Layer 2 Worker B: Customer Diner Portal

**Branch**: `feat/customer-portal`  
**Worktree**: Isolated git worktree (auto-created by Orca)  
**Scope**: 50–70 controllers, 80+ integration tests  
**Duration**: 90 minutes (concurrent with Worker A)  
**Deliverable**: Full portal suite, 0 failing tests

### 3.1 Scope: Restaurant Discovery

**Controllers**: `DiscoveryController`

```php
class DiscoveryController {
    public function index(): void          // GET /discover
    public function show(): void           // GET /discover/:restaurant_id
    public function search(): void         // GET /discover/search?q=...
    public function filterByCuisine(): void // GET /discover?cuisine=italian
    public function filterByAvailability(): void // GET /discover?date=2026-11-25&party_size=4
}
```

**Features**:
- List restaurants: name, cuisine, location, phone, operating hours, average rating
- Search: by name, cuisine tag, location (text search on name/tags)
- Filter: by cuisine, dietary tags (vegan, gluten-free, halal), availability (has slots 7 days out)
- Sort: by rating, distance (stub—no geo), newest
- Detail view: full info, hours, parking, website, reviews (approved only)

**Tests**:
- Search "pizza": returns pizzerias
- Filter vegan + Italian: returns Italian restaurants with vegan tag
- Availability: select date/party size, only show restaurants with open slots
- Multi-tenant visibility: all restaurants visible (discovery is public)

### 3.2 Scope: Slot Availability & Real-Time Booking

**Controllers**: `SlotAvailabilityController`, `BookingFlowController`

#### SlotAvailabilityController
```php
class SlotAvailabilityController {
    public function getSlots(): void // GET /discover/:restaurant_id/availability?date=2026-11-25&party_size=4
}
```

**Features**:
- Return available 15-min slots for date + party size
- Logic: for each table matching capacity (or combinable set), check if all slots free
- Response: list of 30-min windows (e.g., 18:00, 18:30, 19:00, ..., 22:00)
- Green (available), yellow (1 table left), red (booked)

**Tests**:
- 4-top, Nov 25, all slots free: return 18:00–22:00
- 4-top, Nov 25, one 19:00 slot booked: return all except 19:00
- 10-top (combinable 2×5): return only windows where 2 tables free

#### BookingFlowController
```php
class BookingFlowController {
    public function showForm(): void   // GET /bookings/new
    public function create(): void     // POST /bookings
    public function confirmEmail(): void // GET /bookings/:token/confirm
    public function showConfirmation(): void // GET /bookings/:token
}
```

**Features**:
- Booking form: date, time (15-min select), party size, name (optional), email (required)
- Create: validate inputs, allocate tables via TableAllocator, store reservation, send confirmation email (stub)
- Confirm email: click link in email, mark booking confirmed (status = "confirmed")
- Confirmation page: booking number, tables, time, guest count, cancellation link

**Integration Points**:
- **TableAllocator**: atomic allocation (same service as ERP)
- **Email stub**: log to audit_logs, don't actually send

**Tests**:
- Create booking: valid inputs → 201, booking number generated
- Validation: missing email → 422
- Double-booking: allocate table, concurrent request rejects (409 or 422)
- Email confirmation: link valid for 24 hours, then expires

### 3.3 Scope: Menu Sync

**Controllers**: `MenuSyncController`

```php
class MenuSyncController {
    public function getMenu(): void // GET /discover/:restaurant_id/menu
}
```

**Features**:
- Return restaurant's menu respecting 86-list (exclude unavailable items)
- Response: item name, price, description, allergen tags, availability (true/false)
- Cache: update when ERP marks item 86 or reverts

**Integration Points**:
- **Real-time sync**: query menu_items + 86-list join on every request (or cache with 1-min TTL)

**Tests**:
- Menu includes all available items
- Menu excludes 86-list items
- Price displayed in customer format ($19.99)
- Allergen tags visible (nuts, dairy, shellfish)

### 3.4 Scope: Waitlist Portal (Remote Join)

**Controllers**: `WaitlistPortalController`

```php
class WaitlistPortalController {
    public function showForm(): void       // GET /waitlist/:restaurant_id/join
    public function join(): void           // POST /waitlist/:restaurant_id/join
    public function trackPosition(): void  // GET /waitlist/:token/position
    public function cancel(): void         // POST /waitlist/:token/cancel
}
```

**Features**:
- Join form: email, party size, (optional) phone
- Join: add to waitlist, send tracking link (email stub, include :token)
- Track: diner clicks link → shows current position, estimated wait (stub), live updates (polling, no WebSocket)
- Cancel: diner can remove self from waitlist

**Tests**:
- Join waitlist: position auto-assigned
- Track: position updates as ahead of you are seated
- Notification stub: email sent (logged, not actual SMTP)

### 3.5 Scope: Review Submission

**Controllers**: `ReviewSubmissionController`

```php
class ReviewSubmissionController {
    public function form(): void      // GET /reviews/:reservation_token
    public function submit(): void    // POST /reviews
    public function confirmEmail(): void // GET /reviews/:token/confirm
}
```

**Features**:
- Review form: reservation lookup (token), rating (1–5), review text (optional)
- Submit: validate (length, word count), submit pending review, auto-moderate for spam
- Confirm email: click link to finalize review (prevents spam)
- Audit: log submission + moderation status

**Tests**:
- Submit 5-star review: pending, awaiting approval
- Spam: review with >1 link auto-flagged
- Invalid: <5 chars → 422
- Email confirmation: link expires after 24h

### 3.6 Scope: Deposits & Loyalty (Feature-Gated)

**Controllers**: `DepositPortalController`, `LoyaltyPortalController`

#### DepositPortalController
```php
class DepositPortalController {
    public function showBalance(): void   // GET /account/balance
    public function deposit(): void       // POST /account/deposit
}
```

**Features** (if `enable_deposits=true`):
- Balance: sum of deposit_ledger for diner email
- Deposit form: amount, payment method (stub—don't process real payments)
- Audit: log deposit transaction

#### LoyaltyPortalController
```php
class LoyaltyPortalController {
    public function showPoints(): void   // GET /account/loyalty
    public function redeem(): void       // POST /account/loyalty/redeem
}
```

**Features** (if `enable_loyalty=true`):
- Points balance: sum from loyalty_ledger
- Redeem: select redemption (e.g., "$5 off next meal"), apply to booking
- Audit: log redemption

**Tests**:
- Feature disabled: returns 404
- Feature enabled: balance displays correctly
- Redeem: points decremented, booking gets discount

### 3.7 Scope: Deterministic Concierge Rules Engine (Stub)

**Controllers**: `ConciergeController`

```php
class ConciergeController {
    public function determineBestSlots(): void // GET /concierge/best-slots/:restaurant_id?party=4&dietary=vegan
}
```

**Features** (simplified):
- Apply business rules (hours, parking, allergens, reservations available)
- Return top 3 slot recommendations by score (heuristic: minimum travel time, quietest hour, etc.)
- Stub: deterministic logic (no AI), based on hardcoded rules

**Tests**:
- Best slots: returns ranked recommendations

### 3.8 Integration Tests (Worker B)

**File**: `tests/integration/CustomerPortal_Test.php`

**Scope**: 100+ test cases covering:
- Discovery & search
- Availability calculation
- Booking flow (create, confirm, cancel)
- Menu sync (respects 86-list)
- Waitlist join & track
- Review submission & moderation
- Deposits/loyalty (if enabled)
- Multi-tenant isolation (customer data per org)

**Target**: 90%+ coverage on critical paths (BookingFlowController, SlotAvailabilityController)

---

## 4. Merge Sequencing & Conflict Resolution

### 4.1 Parallel Execution (Wave 2)

**Both workers execute simultaneously** (90 minutes):
- Worker A: `git switch feat/client-erp` (worktree A)
- Worker B: `git switch feat/customer-portal` (worktree B)
- Each has isolated tests, controllers, views (no file overlap expected)

### 4.2 Expected Merge Conflicts

**Minimal overlap** (by design):
- `docs/CHANGELOG.md`: both add entries → **Layer 1 manually resolves** (combines entries, keeps chronological order)
- `public/index.php`: both register routes → **Layer 1 concatenates** routes (Worker A first, Worker B second)
- Migrations: each worker may add migrations (Workers don't conflict; Layer 1 sequences by timestamp/number)

### 4.3 Merge Process (Wave 3)

```bash
# Layer 1 merges both branches to main
git checkout main
git merge feat/client-erp
  # Resolve: docs/CHANGELOG.md, routes if needed
git merge feat/customer-portal
  # Resolve: docs/CHANGELOG.md, routes if needed
```

### 4.4 Conflict Resolution Strategy

**CHANGELOG.md**: Combine both [Unreleased] entries into single entry (chronological, no duplication)

**routes (public/index.php)**: Append; structure by prefix:
```php
// Admin ERP routes (Worker A)
$router->get('/admin/restaurants/:id/zones', ...);
// ... more A routes

// Customer Portal routes (Worker B)
$router->get('/discover', ...);
// ... more B routes
```

---

## 5. Gate Criteria (Wave 2 Completion)

### Worker A (ERP) Done When:
- ✓ All 5 controllers: zones, tables, reservations, waitlist, 86-list
- ✓ All 3 ancillary controllers: staff RBAC, analytics, reviews
- ✓ 100+ integration tests, all passing (exit code 0)
- ✓ Multi-tenant scoping verified
- ✓ Audit logging on all CRUD operations
- ✓ CSRF protection on all POST endpoints
- ✓ 0 BROKEN findings from inline code review

### Worker B (Portal) Done When:
- ✓ All 5 controllers: discovery, slots, booking, menu sync, reviews
- ✓ Feature-gated controllers: deposits, loyalty, concierge
- ✓ 100+ integration tests, all passing (exit code 0)
- ✓ Real-time booking with TableAllocator concurrency
- ✓ Menu sync respects 86-list
- ✓ 0 BROKEN findings from inline code review

### Layer 1 Merge & Gate:
- ✓ Both workers report `worker_done outcome: succeeded`
- ✓ Merge conflicts resolved
- ✓ `git status --short` clean after merge
- ✓ `just test` runs full suite: 200+ tests, exit code 0
- ✓ Proceed to Wave 3 (seed, XAMPP package, Red Team audit)

---

## 6. Success Metrics

| Metric | Target | Worker A | Worker B |
|--------|--------|----------|----------|
| Controllers implemented | 8–10 | ✓ | ✓ |
| Integration tests | 100+ | ✓ | ✓ |
| Test coverage (critical paths) | 90%+ | ✓ | ✓ |
| All tests passing | 0 failures | ✓ | ✓ |
| Audit logging | 100% of CRUD | ✓ | ✓ |
| CSRF protection | 100% of POST | ✓ | ✓ |
| Multi-tenant scoping | verified | ✓ | ✓ |
| Code review: BROKEN items | 0 | ✓ | ✓ |

---

**Team Allocation Locked**: 2026-11-20T05:12:00Z  
**Wave 2 Start**: Immediate (post-Wave 1 completion)  
**Wave 2 Duration**: 90 minutes (parallel workers)  
**Wave 3 Merge**: upon both workers' completion

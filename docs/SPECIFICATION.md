# DICT24 Information Management Finals: Complete Product Specification

**Project**: Multi-Tenant Restaurant ERP & Reservation Platform (EIMS)  
**Scope**: Enterprise Information Management System for Restaurant Operations & Customer Engagement  
**Stack**: PHP 8.3 + PDO (MySQL 8.0 primary, SQLite fallback) + Docker Compose  
**Date**: 2026-11-19  
**Status**: Final Phase (Wave 0 Hardening → Wave 1 Core Schema → Wave 2 Parallel Squads → Wave 3 Integration)

---

## 1. Non-Negotiable Architecture & Security Invariants

### 1.1 Runtime & Stack
- **Dockerized PHP 8.3** with `declare(strict_types=1);` on every `.php` file
- **PDO** abstraction layer supporting both **MySQL 8.0** (production) and **SQLite** (automated test parity)
- **Zero external backend frameworks** — pure vanilla PHP, no Laravel/Symfony/Slim/Yii
- **Single Unified UI Shell** with role-based navigation and feature-flag-gated modules

### 1.2 Content Security & Styling Policy
- **Strict CSP**: `Content-Security-Policy: script-src 'self'; style-src 'self'; font-src 'self'; form-action 'self'; frame-ancestors 'none';`
- **Zero Inline Styles**: All `<style>` and `style="..."` attributes prohibited in views, templates, and PHP output
- **CSS Utility Classes**: All layout, state colors, and optional 12×8 floor-plan grids use pre-compiled classes (`gc-1..12`, `gr-1..8`, `span-w-*`, `span-h-*`, `status-*`)
- **No Remote Resources**: No Google Fonts, no CDN scripts, no `@import` URLs. All CSS/JS inline or same-origin.

### 1.3 Authentication & Session Management (No Auth Salad)
- **Stateful Server-Side Sessions** via `$_SESSION` backed by database
- **Immediate Cookie Invalidation**: Logout destroys session and forces new login
- **Per-Form CSRF Tokens**: Every POST/PUT/DELETE form includes `_csrf` token
- **LoginThrottle Anti-Enumeration**: 
  - Per-email max 5 failures / 15 minutes
  - Per-IP max 10 failures / 15 minutes (prevents collateral lockout)
  - Identical HTTP 429 response for both block reasons
- **Prohibited**: OAuth2, OIDC, JWTs, social SSO, hardware MFA, external SMS/payment APIs
- **Simulated Services**: Internal database ledgers for notifications, deposits, and loyalty

### 1.4 Single UI Shell + Feature Flags
- **One Shared Navigation**: Adapts dynamically per user role and tenant features
- **Roles**: `SuperAdmin`, Restaurant `Admin`, `FrontOfHouse`, `Kitchen`, `Customer`
- **Per-Tenant Feature Toggles** in `org_feature_flags`:
  - `enable_floor_plan` — CSP-safe 12×8 CSS Grid floor-plan builder
  - `enable_inventory_erp` — Ingredient BOM, recipes, stock movements
  - `enable_deposits` — Centavo pre-payment holds and forfeiture rules
  - `enable_loyalty` — Visit-based loyalty points ledger
- **Consistent UX**: Feature-gated sections hide cleanly; no broken links or 404 errors

### 1.5 Database Integrity & Concurrency Guarantees

#### 1.5.1 Half-Open UTC Intervals
- Reservations store `[start_at, end_at)` where `end_at = start_at + turn_duration_minutes + buffer_minutes`
- Two bookings overlap iff `existing.start_at < new.end_at AND existing.end_at > new.start_at`
- Applies only to active statuses: `pending`, `confirmed`, `seated`

#### 1.5.2 Two-Layer Double-Booking Lock
1. **Transactional Wrapping**: `PDO::beginTransaction()` on every booking/modification
2. **Table Row Lock**:
   - MySQL: `SELECT ... FROM dining_tables WHERE id IN (...) FOR UPDATE ORDER BY id ASC`
   - SQLite: `BEGIN IMMEDIATE`
3. **Overlap Verification**: Query existing active reservations, verify zero overlaps
4. **15-Minute Slot Bucketing**: Insert discretized `reservation_slots (org_id, table_id, slot_start_utc)` with `UNIQUE(org_id, table_id, slot_start_utc)`
5. **Atomic Commit**: Single transaction guarantees consistency

#### 1.5.3 Multi-Table Combinable Booking
- Link bookings to tables via `reservation_tables (reservation_id, table_id)` (1:N)
- Parties larger than one table lock adjacent `is_combinable = 1` tables in the same `dining_zone`
- All locks acquired in deterministic order before verification/insertion

#### 1.5.4 Safe Mutations & Soft Archiving
- **Foreign Key Constraints**: `ON DELETE RESTRICT` (prevent orphaned data)
- **Soft Archiving**: Tables, zones, menu items use `archived_at DATETIME NULL`
- **Mutation Guards**: Block archiving or capacity reduction if any future active reservation (`end_at > NOW()`) references it

#### 1.5.5 Centavos & Append-Only Ledgers
- **Currency**: All amounts stored as signed integer centavos (`BIGINT`)
- **Immutable Ledgers**:
  - `deposit_ledger (id, org_id, customer_id, amount_centavos, action, reason, created_at)`
  - `stock_movements (id, org_id, ingredient_id, quantity_delta, reason, created_at)`
- **No Updates**: Only inserts; historical audit trail preserved

### 1.6 Quality Gates
Before accepting any worker output:
- `php -l` on all PHP files: zero syntax errors
- `grep -r 'style=' src/ docs/specimens/` on generated files: zero matches
- `just test` exit code: `0` (all test checks pass)

---

## 2. Database Schema (Multi-Tenant + Operations)

### 2.1 Core Tables
```
organizations (org_id, name, subscription_tier, marketplace_visible, status, created_at, archived_at)
org_feature_flags (org_id, flag_name, enabled)
org_invites (invite_id, org_id, email, token_hash, created_at, expires_at, claimed_by_user_id)
users (user_id, org_id, email, password_hash, first_name, last_name, role_mask, created_at, archived_at)
sessions (session_id, user_id, org_id, data, expires_at)
audit_logs (log_id, org_id, actor_user_id, action, target_entity, target_id, before_snapshot, after_snapshot, created_at)
```

### 2.2 Restaurant Operations
```
dining_zones (zone_id, org_id, name, capacity, is_combinable, archived_at)
dining_tables (table_id, org_id, zone_id, label, capacity, x_coord, y_coord, is_combinable, archived_at)
reservations (reservation_id, org_id, table_id, customer_id, party_size, start_at, end_at, status, notes, created_at, updated_at)
reservation_tables (reservation_id, table_id)
reservation_slots (org_id, table_id, slot_start_utc) — UNIQUE constraint for bucket locking
waitlist_entries (waitlist_id, org_id, customer_id, party_size, preferred_zone, join_at, position, status, created_at)
```

### 2.3 Menu & Inventory
```
menu_items (item_id, org_id, name, description, price_centavos, is_available, dietary_tags, allergen_tags, archived_at)
ingredients (ingredient_id, org_id, name, unit, on_hand, reorder_level, archived_at)
menu_item_recipes (recipe_id, item_id, ingredient_id, quantity_required)
purchase_orders (po_id, org_id, ingredient_id, quantity, status, created_at, received_at)
stock_movements (movement_id, org_id, ingredient_id, quantity_delta, reason, created_at)
```

### 2.4 Customer Engagement
```
reviews (review_id, org_id, reservation_id, rating, comment, created_at, restaurant_response, response_at)
deposit_ledger (ledger_id, org_id, customer_id, amount_centavos, action, reason, created_at)
loyalty_ledger (ledger_id, org_id, customer_id, points_delta, reason, created_at)
login_attempts (id, scope, subject, attempted_at) — anti-brute-force tracking
```

---

## 3. Core Services (Wave 1)

### 3.1 FeatureFlagService
```php
- isEnabled(org_id, flag_name): bool
- toggle(org_id, flag_name, enabled): void
- allFlags(org_id): array
```

### 3.2 AuditLogger
```php
- log(org_id, actor_id, action, target_entity, target_id, before, after): void
- listByOrg(org_id, limit, offset): array
- listByUser(org_id, user_id, limit, offset): array
```

### 3.3 TableAllocator
```php
- allocateReservation(org_id, table_ids[], start_at, end_at): reservation_id
- modifyReservation(reservation_id, new_table_ids[], new_start_at, new_end_at): void
- cancelReservation(reservation_id): void
- getAvailableSlots(org_id, date, party_size, zone_filter): array
```

### 3.4 DepositLedgerService
```php
- holdDeposit(org_id, customer_id, amount_centavos, reason): ledger_id
- forfeitDeposit(org_id, customer_id, amount_centavos, reason): void
- refundDeposit(org_id, customer_id, amount_centavos, reason): void
- getBalance(org_id, customer_id): int (centavos)
```

### 3.5 StockLedgerService
```php
- recordMovement(org_id, ingredient_id, quantity_delta, reason): void
- getOnHand(org_id, ingredient_id): float
- checkReorderThreshold(org_id): array (low-stock ingredients)
```

---

## 4. Endpoint Inventory

### 4.1 Public Routes
- `GET /` — Landing page
- `GET /login` — Login form
- `POST /login` — Process login (with CSRF)
- `GET /health` — Health probe (no auth)
- `GET /diagnostics/sabotage` — Intentional 500 error (testing only)

### 4.2 SuperAdmin Routes
- `GET /superadmin/dashboard` — Metrics, pending invites
- `GET /superadmin/organizations` — List all orgs with lifecycle controls
- `GET|POST /superadmin/organizations/edit` — Provision/edit organization
- `GET|POST /superadmin/organizations/delete` — Archive organization
- `GET /superadmin/users` — Global user roster
- `GET /superadmin/audit-logs` — Cross-tenant audit viewer
- `POST /superadmin/revoke-session` — Immediate session termination

### 4.3 Admin (Restaurant) Routes
- `GET /admin/dashboard` — Table status, pending reservations, analytics
- `GET /admin/users` — Tenant user roster
- `GET|POST /admin/users/edit` — Provision/edit tenant staff
- `GET|POST /admin/users/delete` — Deactivate staff
- `GET|POST /admin/tables` — Manage dining tables
- `GET|POST /admin/tables/edit` — Table capacity, combinability
- `GET|POST /admin/menu` — Menu CRUD
- `POST /admin/menu-items/toggle-86` — Mark item unavailable
- `GET /admin/waitlist` — Walk-in queue management
- `POST /admin/waitlist/promote` — Backfill reserved table to waitlist
- `GET /admin/inventory` — Stock on-hand, reorder alerts
- `GET /admin/analytics` — Turnover, peak hours, no-show %, top items
- `GET /admin/reviews` — Customer ratings + response dashboard

### 4.4 FrontOfHouse (Host/Server) Routes
- `GET /foh/dashboard` — Quick-access table board + waitlist
- `POST /foh/tables/status` — Update table state (available → occupied → cleaning)
- `GET /foh/reservations` — Today's bookings, check-in flow
- `POST /foh/reservations/checkin` — Assign table, transition to `seated`

### 4.5 Kitchen Routes
- `GET /kitchen/orders` — Prep queue (linked to active reservations)
- `POST /kitchen/orders/mark-ready` — Signal dish ready

### 4.6 Customer Portal Routes
- `GET /restaurants` — Marketplace search (org name, cuisine, zone, dietary support)
- `POST /restaurants/search` — Filter by filters
- `GET /restaurants/:org_id` — Org detail + availability calendar
- `GET /restaurants/:org_id/menu` — Interactive menu with allergen tags
- `GET|POST /bookings/new` — Reservation form (party size, zone, dietary/allergy notes)
- `POST /bookings/confirm` — Process booking, generate signed link
- `GET /bookings/:booking_code/confirm` — Signed self-service confirmation
- `GET /bookings/:booking_code/modify` — Change date/time/party size
- `GET /bookings/:booking_code/cancel` — Self-service cancellation
- `GET /waitlist/:org_id/join` — Remote walk-in join
- `GET /waitlist/:org_id/status/:token` — Queue position + simulated `(Mock)` SMS alerts
- `POST /chat/concierge` — Deterministic SQL chatbot (hours, location, allergen lookup, reservation status)
- `POST /reviews/:reservation_id` — Post-dining rating + comment

### 4.7 Shared Routes
- `POST /logout` — Clear session, invalidate cookie
- `GET /user/dashboard` — Role-specific summary

---

## 5. API Response Contract

All endpoints return JSON (except HTML views) with standard wrapper:
```json
{
  "success": true|false,
  "data": {...},
  "errors": ["message1", "message2"],
  "metadata": {"timestamp": "ISO-8601", "version": "1.0"}
}
```

**Error Codes**:
- `200` — Success
- `201` — Created
- `204` — No Content
- `400` — Validation error (missing fields, type mismatch)
- `401` — Unauthenticated
- `403` — Unauthorized (role mismatch)
- `404` — Not Found
- `409` — Conflict (double-booking, stale reservation)
- `422` — Unprocessable (business logic violation)
- `429` — Rate Limited (login throttle)
- `500` — Server Error (APP_DEBUG=false hides trace; logs to stderr)

---

## 6. Feature-Flag Gating Reference

### Floor Plan Builder (enable_floor_plan)
- Tenant admin can drag-and-drop tables on a 12×8 CSS Grid
- Coordinates map to CSS classes: `gc-1..12` (column), `gr-1..8` (row)
- No inline `style=` attributes; positions defined in `dining_tables.x_coord, y_coord` → CSS class generation
- Hidden from view if flag disabled; edit form returns 403

### Inventory ERP (enable_inventory_erp)
- Ingredient CRUD, recipe BOM, purchase orders, stock movements
- All tables soft-archived when flag disabled; queries filter `archived_at IS NULL`
- Menu CRUD still available; inventory links hidden from response

### Deposits (enable_deposits)
- Customer checkout can require pre-payment hold
- Forfeiture/refund logic gated; ledger always records attempts
- Customer portal shows "Deposit Required" vs. "Deposit Waived"

### Loyalty (enable_loyalty)
- Post-dinner loyalty points awarded (visit count, referral bonus, tier multiplier)
- Ledger tracks all movements; customer can view balance
- Disabled: ledger still records, but customer UI doesn't render balance/redemption

---

## 7. Error Handling & Logging

### Exception Handling
- **Development** (`APP_DEBUG=true`): Full stack trace, line numbers, file paths, exception details
- **Production** (`APP_DEBUG=false`): Generic "An error occurred" message; trace/details logged to stderr + structured log file

### Audit Trail
- Every create/update/delete logged to `audit_logs` with actor, timestamp, before/after snapshot
- Accessible only to SuperAdmin (via `/superadmin/audit-logs`) and relevant tenant admins (scoped view)
- Immutable: insert-only, no update/delete on log entries

---

## 8. Testing Requirements

### Unit Tests
- Service layer: `FeatureFlagService`, `AuditLogger`, `TableAllocator`, `DepositLedgerService`, `StockLedgerService`
- Validator: Input sanitization, CSRF token validation
- LoginThrottle: Per-email vs per-IP isolation, anti-enumeration

### Integration Tests
- Multi-tenant cross-contamination: Admin A cannot read/edit Org B data
- Double-booking prevention: Simultaneous attempts on same table/overlapping window fail atomically
- Multi-table combinable booking: Large parties lock adjacent tables correctly
- Feature-flag gating: Disabled features return 404 or hidden from nav
- Archival guards: Cannot archive table with active future reservations
- Soft-delete queries: Archived records excluded from listings

### Concurrency Stress Tests
- 50+ simultaneous booking attempts on same table/slot
- Verify zero double-bookings, atomic success/failure
- SQLite + MySQL both tested

### Security Tests (Red Team)
- Authorization matrix (4 roles × 10+ routes)
- Cross-tenant reads/writes
- XSS: Stored and reflected
- CSRF: Token validation
- Session invalidation on logout
- LoginThrottle: Per-email and per-IP limits
- Inline styles: Zero matches in src/ and docs/specimens/
- Stack traces: Hidden in production mode

---

## 9. Deployment & Operations

### Environment Variables
- `APP_ENV` — `development` or `production`
- `APP_DEBUG` — `false` (default, production-safe); `true` only in development
- `DB_CONNECTION` — `mysql` or `sqlite`
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `DB_SQLITE_PATH` — Path to fallback SQLite file
- `TRUSTED_PROXIES` — Comma-separated IPs for `X-Forwarded-For` trust

### Docker Compose
- Three services: `web` (nginx), `app` (PHP), `db` (MySQL)
- Volume mounts: `./src:/var/www/html`, `./storage:/var/www/html/storage` (persistent)
- Default port: `8080`
- Health checks: `/health` endpoint returns `{"status":"ok"}`

### Database Migrations
- Idempotent SQL files in `src/database/migrations/`
- Compatible with MySQL 8.0 and SQLite 3
- Executed on container startup via Docker entrypoint
- Rollback: Manual SQL or schema recreation (development only)

---

## 10. Glossary & Definitions

- **Tenant**: A restaurant organization with isolated data, users, and feature flags
- **SuperAdmin**: Global system administrator (cross-tenant powers)
- **Admin/Manager**: Tenant restaurant manager (can provision staff, configure menu/tables)
- **FrontOfHouse**: Host, hostess, or server (table status updates, check-in flow)
- **Kitchen**: Cook, prep (order prep queue)
- **Customer**: Diner using the portal to search, book, and review restaurants
- **Active Reservation**: `status IN ('pending', 'confirmed', 'seated')` and `end_at > NOW()`
- **Combinable Table**: `is_combinable = 1`; can be locked together with adjacent tables for large parties
- **Slot**: 15-minute discretized time bucket for double-booking prevention
- **Centavo**: 1/100th of a currency unit; all monetary amounts stored as signed integers
- **Feature Flag**: Per-tenant boolean toggles gating optional functionality
- **Soft Archive**: `archived_at` timestamp; record retained for audit but excluded from active queries

---

## 11. Known Constraints & Trade-Offs

1. **Per-Email Targeted Lockout**: Designed trade-off — 5 email-scoped failures lock that account for 15 min. Mitigation: High-IP limit (10) prevents collateral damage on shared networks. Future: CAPTCHA or device fingerprinting.
2. **No Real SMS/Push Notifications**: Simulated via `(Mock)` in-app alerts and database ledger entries. Real integrations deferred to post-finals.
3. **Single Database Connection**: No read replicas. Suitable for class project; production needs replication strategy.
4. **No Real Payment Processing**: Simulated via `deposit_ledger` with mock hold/forfeit/refund rules.
5. **No AI/ML**: Deterministic SQL concierge chatbot, no NLP or ML models.

---

**End of Specification**

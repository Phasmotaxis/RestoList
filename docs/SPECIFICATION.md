# DCIT 24 Restaurant ERP & Reservation Platform: Unified Specification

**University**: Cavite State University - Main Campus  
**College**: College of Engineering and Information Technology (CEIT)  
**Department**: Department of Information Technology (DIT)  
**Course**: DCIT 24 - Information Management  
**Academic Term**: 1st Semester, Academic Year 2026-2027  
**Submission Date**: December 18, 2026  
**Author**: EIMS Development Team  
**Version**: 1.0.0  
**Status**: LOCKED for Waves 0–3 implementation  
**Revision**: Final (no changes during Wave 0–3 execution without Red Team consensus)

---

## 1. Executive Summary

DICT24 Restaurant ERP & Reservation Platform is a multi-tenant, real-time reservation and dining management system for restaurant chains and independent venues. The platform operates in three distinct user spaces:

1. **SuperAdmin Portal**: Organization & tenant management, feature flags, audit logging
2. **Restaurant Client ERP**: Dining zone floor plan management, table reservations, waitlist, staff RBAC, kitchen integration, 86-list (item unavailability), analytics
3. **Customer Diner Portal**: Restaurant discovery, real-time slot availability, online table booking, menu browsing, reviews, deposit/loyalty balances (feature-gated)

The system enforces multi-tenant isolation via `organization_id` scoping on every query, implements concurrency-safe table allocation with 15-minute slot boundaries, and provides immutable audit trails for compliance.

**Database**: Dual-driver (MySQL 8.0 primary, SQLite3 fallback)  
**Backend**: Native PHP 8.3 (no external frameworks)  
**Frontend**: Semantic HTML5, CSS3 custom properties, zero inline styles  
**Deployment**: Docker (dev) → XAMPP (production)

---

## 2. Business Requirements

### 2.1 Multi-Tenant Model

**Organization (Tenant)**: A restaurant chain or independent venue owner.
- Can operate multiple restaurants (dining locations)
- Controls feature flags for all its restaurants
- Isolated audit logs, user accounts, reservations, inventory
- Cannot access or modify data from other organizations

**Subscription Tiers** (for future monetization):
- `free`: 1 restaurant, basic features
- `professional`: 5 restaurants, ERP + floor plan
- `enterprise`: unlimited restaurants, all features + API access

### 2.2 Three User Spaces

#### A. SuperAdmin Portal
**Access**: SuperAdmin role only (created at org setup)

**Features**:
- List/create/edit/archive organizations
- Toggle per-org feature flags (enable_floor_plan, enable_inventory_erp, enable_deposits, enable_loyalty)
- View audit logs across all orgs (org_id, action, actor, before/after)
- Issue admin invites (hashed tokens, email delivery stub)
- Revoke active sessions (emergency access control)
- Dashboard: active orgs, user count, recent audits

**Routes**:
```
GET    /superadmin/tenants
POST   /superadmin/tenants                      (create org)
GET    /superadmin/tenants/:id/edit
POST   /superadmin/tenants/:id                  (update org)
GET    /superadmin/tenants/:id/flags            (show flag toggles)
POST   /superadmin/tenants/:id/flags/:flag/toggle
POST   /superadmin/tenants/:id/invite           (issue admin token)
GET    /superadmin/audit
GET    /superadmin/sessions
POST   /superadmin/sessions/:id/revoke
```

#### B. Restaurant Client ERP
**Access**: Admin (org-scoped), Manager (restaurant-scoped), Staff (user-scoped)

**Subdomain 1: Floor Plan & Table Management**
- 12×8 grid-based dining zones
- Table creation (capacity, combinable flag, coordinates)
- Real-time occupancy display (available, reserved, occupied, cleaning, cancelled)
- Drag-and-drop (UI stub—backend supports coordinates)

**Subdomain 2: Reservations & Booking**
- Create reservation: select tables, party size, time window (15-min boundary snapping)
- Allocate tables: atomic transaction, double-booking prevention via `reservation_slots` unique constraint
- Edit/cancel: deallocate slots, audit log, immediate rebooking availability
- View future/past reservations by zone, date, guest name

**Subdomain 3: Waitlist & Walk-ins**
- Add to waitlist: email, party size, zone preference, position
- Live position tracking (updates as seated guests leave)
- Backfill algorithm: when table frees, auto-match first waitlist entry
- Notifications (email stub): "Your table is ready"

**Subdomain 4: 86-List (Menu Item Unavailability)**
- Mark menu items as unavailable (86) by restaurant
- Real-time menu sync to Customer Portal
- Revert when stock replenished
- Audit: timestamp, actor, reason

**Subdomain 5: Staff RBAC**
- Roles: FrontOfHouse (servers, hosts), Kitchen (chefs, prep), Manager (restaurant admin)
- Permissions: FrontOfHouse can edit reservations; Kitchen can view orders only; Manager can do all
- Session enforcement: middleware blocks unauthorized actions with HTTP 403

**Subdomain 6: Analytics**
- Turnover %: seated minutes / table capacity / opening hours
- Peak hours: hourly distribution of reservations
- No-show %: cancelled or no-show vs. completed
- Top items: revenue by menu item, 86-frequency
- Revenue: sum of completed reservations with deposit amounts

**Routes** (abbreviated; full routes in Wave 2):
```
GET    /admin/restaurants/:id/zones
POST   /admin/restaurants/:id/zones             (create zone)
GET    /admin/restaurants/:id/tables
POST   /admin/restaurants/:id/tables            (add table)
GET    /admin/restaurants/:id/reservations
POST   /admin/restaurants/:id/reservations      (create reservation)
GET    /admin/restaurants/:id/waitlist
POST   /admin/restaurants/:id/waitlist          (add walk-in)
GET    /admin/restaurants/:id/menu/86
POST   /admin/restaurants/:id/menu/:item_id/86 (toggle 86-status)
GET    /admin/restaurants/:id/staff
GET    /admin/restaurants/:id/analytics
```

#### C. Customer Diner Portal
**Access**: Unauthenticated visitor or logged-in diner (email-based account)

**Subdomain 1: Restaurant Discovery**
- Search by cuisine, location, dietary tags (vegan, gluten-free, halal)
- Filter by availability (slots in next 7 days)
- Browse reviews (diner-submitted, staff-moderated)
- View operating hours, parking, phone

**Subdomain 2: Real-Time Booking Flow**
- Select date, time (15-min slots), party size
- See available tables (no floor plan reveal; abstract counts: "2 tables available for 4 people")
- Book: email + optional name, phone
- Confirmation: email with booking number, cancellation link

**Subdomain 3: Menu Sync**
- Live menu from restaurant ERP
- Respects 86-list (unavailable items grayed out)
- Allergen tags visible (nuts, dairy, shellfish)
- Menu history (for reviews)

**Subdomain 4: Deposits & Loyalty (Feature-Gated)**
- If `enable_deposits=true`: show balance, allow pre-payment for booking
- If `enable_loyalty=true`: show points earned, redeem for discounts
- Ledger: immutable append-only, signed amounts

**Subdomain 5: Reviews & Ratings**
- Post review after dining (accessible 24 hours after reservation completion)
- Rating 1–5, optional text
- Auto-moderation: length checks, spam heuristic
- Staff review reply (admin-only view)

**Routes** (abbreviated):
```
GET    /discover
GET    /discover/:id                           (restaurant details)
GET    /discover/:id/availability
POST   /bookings                               (create booking)
GET    /bookings/:token/confirm                (confirm via email link)
GET    /menu/:restaurant_id
GET    /reviews/:reservation_id/new            (review form)
POST   /reviews                                (submit review)
GET    /account/balance                        (deposits/loyalty if enabled)
```

---

## 3. Data Contract & Schema

### 3.1 Database Tables (18 Total)

**Core Tenant & Auth (4 tables)**:
- `organizations` (id, name, subscription_tier, feature_flags JSON, created_at, archived_at)
- `org_feature_flags` (id, org_id, enable_floor_plan, enable_inventory_erp, enable_deposits, enable_loyalty, updated_at)
- `org_invites` (id, org_id, invite_code_hashed, created_at, expires_at)
- `audit_logs` (id, org_id, actor_id, action, target_entity, before_json, after_json, created_at)

**Users & Sessions (2 tables)**:
- `users` (id, email, password_hash, role, org_id, restaurant_id, created_at, last_login_at)
- `sessions` (id, user_id, token_hash, created_at, expires_at, last_activity_at)

**Restaurant Operations (6 tables)**:
- `dining_zones` (id, org_id, name, description, archived_at)
- `dining_tables` (id, org_id, zone_id, table_number, capacity, is_combinable, coordinates_x, coordinates_y, is_active)
- `reservations` (id, org_id, table_ids JSON, party_size, customer_email, start_at_utc, end_at_utc, status ENUM, notes, created_at)
- `reservation_tables` (reservation_id, table_id, PRIMARY KEY)
- `reservation_slots` (org_id, table_id, slot_start_utc, PRIMARY KEY, UNIQUE for double-booking prevention)
- `waitlist_entries` (id, org_id, customer_email, party_size, zone_preference, position, status, created_at)

**Menu & Reviews (2 tables)**:
- `menu_items` (id, org_id, name, price_centavos INT, tags JSON, is_available, created_at)
- `reviews` (id, org_id, reservation_id, rating 1–5, review_text, approved BOOL, created_at)

**Financial (2 tables, feature-gated)**:
- `deposit_ledger` (id, org_id, reservation_id, amount_centavos INT signed, type ENUM, reason, created_at, immutable append-only)
- `loyalty_ledger` (id, org_id, diner_email, points_earned INT, points_redeemed INT, created_at, immutable append-only)

**ERP Inventory (2 tables, feature-gated)**:
- `ingredients` (id, org_id, name, unit_of_measure, created_at)
- `stock_movements` (id, org_id, ingredient_id, quantity_delta INT, reason, created_at, immutable append-only)

### 3.2 SQL Dialect Branching (MySQL vs SQLite)

**At runtime** (`$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)`):

| Feature | MySQL 8.0 | SQLite3 |
|---------|-----------|---------|
| **Timestamps** | `NOW()` | `CURRENT_TIMESTAMP` |
| **Auto-increment** | `AUTO_INCREMENT` | `AUTOINCREMENT` |
| **Integer 64-bit** | `BIGINT` | `INTEGER` (64-bit by default) |
| **Unsigned 32-bit (centavos)** | `INT UNSIGNED` | `INTEGER` (treated as signed; validate in app) |
| **JSON columns** | `JSON` type, native operators | `TEXT`, parsed in app layer |
| **Row-level locking** | `SELECT...FOR UPDATE` (IMMEDIATE trans) | `BEGIN IMMEDIATE`, SQLite implicit |
| **Full-text search** | `FULLTEXT INDEX` | `LIKE` % pattern matching |

**Parameterized Queries** (100% across all drivers):
```php
// ✓ CORRECT (both drivers)
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute([':email' => $userEmail]);

// ✗ FORBIDDEN (SQL injection risk)
$sql = "SELECT * FROM users WHERE email = '$userEmail'";
```

### 3.3 Centavo Arithmetic

**Principle**: All monetary amounts stored as integers (centavos), never floats.

```php
// Store: $19.99 → 1999 (centavos)
$priceCentavos = (int)round($priceDecimal * 100);

// Retrieve: 1999 → "$19.99"
$priceDecimal = $priceCentavos / 100.0;
printf("$%.2f", $priceDecimal);
```

**Ledger integrity**: `SUM(amount_centavos)` is exact; no rounding errors.

### 3.4 UTC Timezone

**All timestamps** stored as UTC in database (`TIMESTAMP` or `TEXT`).

```php
// Create/update
$pdo->prepare('INSERT INTO reservations (start_at_utc) VALUES (:start_utc)')
    ->execute([':start_utc' => (new DateTime('UTC'))->format('Y-m-d H:i:s')]);

// Query (timezone-agnostic)
$start = DateTime::createFromFormat('Y-m-d H:i:s', $row['start_at_utc'], new DateTimeZone('UTC'));
```

---

## 4. API Contract

### 4.1 Request/Response Schemas

All requests/responses use **JSON** (Content-Type: `application/json`).

#### Example: Create Reservation (POST /admin/restaurants/:id/reservations)

**Request**:
```json
{
  "table_ids": [1, 2],
  "party_size": 4,
  "customer_email": "diner@example.com",
  "start_at_utc": "2026-11-25T19:00:00Z",
  "end_at_utc": "2026-11-25T20:30:00Z",
  "notes": "Anniversary dinner, allergies on file"
}
```

**Response (201 Created)**:
```json
{
  "id": 42,
  "org_id": 1,
  "table_ids": [1, 2],
  "party_size": 4,
  "customer_email": "diner@example.com",
  "start_at_utc": "2026-11-25T19:00:00Z",
  "end_at_utc": "2026-11-25T20:30:00Z",
  "status": "confirmed",
  "created_at": "2026-11-20T10:15:00Z"
}
```

**Response (422 Unprocessable Entity - Validation Error)**:
```json
{
  "error": "Validation failed",
  "fields": {
    "party_size": "Must be between 1 and table capacity",
    "start_at_utc": "Cannot book in the past"
  }
}
```

### 4.2 Error Codes

| Code | Scenario | Response |
|------|----------|----------|
| **200** | Successful GET/PUT/PATCH | JSON data + headers |
| **201** | Successful POST (create) | JSON data + `Location: /resource/:id` header |
| **204** | Successful DELETE or no-content response | Empty body |
| **400** | Malformed JSON, missing required fields (syntax) | `{"error": "Bad request"}` |
| **403** | Forbidden (wrong role, cross-tenant IDOR, CSRF token mismatch) | `{"error": "Access denied"}` |
| **404** | Not found (restaurant, reservation, user, etc.) | `{"error": "Not found"}` |
| **422** | Validation failed (business logic) | `{"error": "Validation failed", "fields": {...}}` |
| **429** | Rate limited (LoginThrottle per-email) | `{"error": "Too many attempts. Retry after 15 minutes"}` |
| **500** | Server error (unexpected exception, DB down) | `{"error": "Internal server error"}` (no stack trace when `APP_DEBUG=false`) |

### 4.3 Pagination Defaults

**List endpoints** (GET /resources):

```
Query params: ?page=1&per_page=20&sort=created_at&order=desc

Response:
{
  "data": [...],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 156,
    "pages": 8
  }
}
```

---

## 5. Security Invariants

### 5.1 Multi-Tenant Org/Restaurant Scoping

**Every SELECT, UPDATE, DELETE must filter by org_id or restaurant_id**:

```php
// ✓ CORRECT (org-scoped)
$stmt = $pdo->prepare('SELECT * FROM reservations WHERE org_id = :org_id AND id = :id');
$stmt->execute([':org_id' => $orgId, ':id' => $reservationId]);

// ✗ FORBIDDEN (cross-tenant data leak)
$stmt = $pdo->prepare('SELECT * FROM reservations WHERE id = :id');
$stmt->execute([':id' => $reservationId]);
```

### 5.2 CSRF Protection

Every POST/PUT/DELETE form includes a CSRF token:

```html
<form method="POST" action="/admin/reservations">
  <input type="hidden" name="_csrf" value="{{ csrf_token() }}">
  <!-- fields -->
</form>
```

**Validation** (server-side):
```php
if ($_POST['_csrf'] !== Session::getCsrfToken()) {
    http_response_code(403);
    exit('CSRF token mismatch');
}
```

**Missing token** → 403 Forbidden (not 200 or 500).

### 5.3 LoginThrottle: Per-Email Anti-Enumeration

Threshold: **5 failed attempts per email** (15-minute window), regardless of IP.

```php
// In AuthController@login
if (LoginThrottle::isBlocked($email)) {
    http_response_code(429);
    exit('Too many login attempts. Try again in 15 minutes.');
}
```

**Prevents** account enumeration attacks ("this email is registered") while allowing shared networks (office, family WiFi) to work normally.

### 5.4 CSP: Zero Inline Styles

All CSS in `public/assets/css/app.css` (or external `<link>`); **zero `style=""` attributes**.

```html
<!-- ✓ CORRECT -->
<div class="status-reserved">Reserved</div>

<!-- ✗ FORBIDDEN -->
<div style="background: blue; color: white;">Reserved</div>
```

**Content-Security-Policy header**:
```
style-src 'self' https://fonts.googleapis.com;
script-src 'self';
img-src 'self' data:;
default-src 'self';
```

### 5.5 Input Validation

**Email**: RFC 5322 pattern (`Validator::email()`)  
**Phone**: E.164 format or local variant (`Validator::phone()`)  
**Party size**: 1–20 (business logic)  
**Dates**: ISO 8601 UTC, no past bookings  
**Prices**: Non-negative integers (centavos)

**Error handling**: Validation errors → 422 with field-level messages (not 500).

### 5.6 Audit Logging

Every INSERT/UPDATE/DELETE call:

```php
Audit::log(
    org_id: $orgId,
    actor_id: Session::get('user_id'),
    action: 'CREATE', // or UPDATE, DELETE
    target_entity: 'reservation',
    before: null,
    after: $newReservationData
);
```

**Immutable**: audit_logs cannot be deleted or modified (append-only, enforce via DB privilege or app layer).

---

## 6. Feature Flags (Per-Organization)

**Flags** stored in `organizations.feature_flags` JSON or `org_feature_flags` table:

| Flag | Default | Effect |
|------|---------|--------|
| `enable_floor_plan` | false | Show/hide floor plan UI in Admin ERP |
| `enable_inventory_erp` | false | Show/hide inventory (ingredients, suppliers, stock moves) in Admin ERP |
| `enable_deposits` | false | Allow customers to pre-pay deposits (ledger, balance display) |
| `enable_loyalty` | false | Track loyalty points (ledger, redemption) |

**At runtime**:
```php
if (FeatureFlagService::isEnabled($orgId, 'enable_floor_plan')) {
    // Show floor plan editor
} else {
    // Redirect to 404 or show "feature unavailable"
    http_response_code(404);
}
```

---

## 7. Concurrency Safety & Table Allocation

### 7.1 Double-Booking Prevention

**Problem**: Two simultaneous requests for same table, overlapping time window → only one should succeed.

**Solution**: 
1. 15-minute slot boundary snapping (round down to nearest 15-min)
2. `reservation_slots` table with `UNIQUE KEY (org_id, table_id, slot_start_utc)`
3. Transactional locking: `BEGIN IMMEDIATE`, `SELECT...FOR UPDATE`, INSERT slots, COMMIT

**Flow** (TableAllocator):
```php
$pdo->exec('BEGIN IMMEDIATE');
try {
    // SELECT...FOR UPDATE to lock rows
    $stmt = $pdo->prepare('SELECT id FROM reservation_slots WHERE org_id = :org_id AND table_id = :table_id FOR UPDATE');
    $stmt->execute([':org_id' => $orgId, ':table_id' => $tableId]);
    
    // Check: existing slot in window?
    if ($stmt->rowCount() > 0) {
        throw new Exception('Table already booked');
    }
    
    // INSERT new slot (UNIQUE constraint enforced)
    $stmt = $pdo->prepare('INSERT INTO reservation_slots (org_id, table_id, slot_start_utc) VALUES (?, ?, ?)');
    $stmt->execute([$orgId, $tableId, $slotStart]);
    
    $pdo->exec('COMMIT');
    return true;
} catch (Exception $e) {
    $pdo->exec('ROLLBACK');
    return false; // Conflict; caller retries or shows "table unavailable"
}
```

### 7.2 Reservation Cancellation

When guest cancels:
```php
// 1. DELETE from reservation_slots (free up the slot)
$pdo->prepare('DELETE FROM reservation_slots WHERE reservation_id = ?')
    ->execute([$reservationId]);

// 2. UPDATE reservation (mark cancelled)
$pdo->prepare('UPDATE reservations SET status = "cancelled" WHERE id = ?')
    ->execute([$reservationId]);

// 3. Audit log
Audit::log(..., 'DELETE', 'reservation', ...);

// 4. Trigger waitlist backfill (if enabled)
WaitlistService::backfill($orgId, $tableId, $slot);
```

---

## 8. Ledger Integrity (Deposits & Loyalty)

**Immutable append-only ledger**:

```sql
CREATE TABLE deposit_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT NOT NULL,
    reservation_id INT NOT NULL,
    amount_centavos INT NOT NULL,  -- signed (positive = deposit, negative = refund)
    type ENUM('deposit', 'refund', 'applied') NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id)
);
```

**Balance calculation** (never update ledger):
```php
$balance = $pdo->query(
    'SELECT COALESCE(SUM(amount_centavos), 0) AS balance FROM deposit_ledger WHERE org_id = ? AND reservation_id = ?'
)->fetch()['balance'];
```

---

## 9. Testing Strategy

### 9.1 Test Coverage Targets

- **Unit tests**: Validator, LoginThrottle, FeatureFlagService (logic)
- **Integration tests**: Controllers (HTTP lifecycle, DB transactions, audit)
- **Concurrency tests**: TableAllocator (simultaneous bookings)
- **Security tests**: CSRF, IDOR, multi-tenant scoping, input validation

**Target**: 200+ tests, 90%+ coverage on critical paths (TableAllocator, Audit, Auth, RBAC).

### 9.2 Test Environment

- **DB**: SQLite3 in-memory (`:memory:`) for fast isolation
- **HTTP**: Mock Request/Response (no network)
- **Email**: Stub (capture, don't send)

---

## 10. Deployment & Configuration

### 10.1 Environment Variables (.env.example)

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=                          # Base64-encoded 32-byte encryption key
APP_URL=https://app.example.com

DB_CONNECTION=mysql               # mysql or sqlite
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=eims_prod
DB_USERNAME=eims_user
DB_PASSWORD=                       # Strong random password
DB_SQLITE_PATH=/var/www/storage/fallback.sqlite

CACHE_DRIVER=file
SESSION_DRIVER=database

MAIL_FROM_ADDRESS=noreply@restaurant.com
MAIL_FROM_NAME=DICT24 Restaurant ERP

FEATURE_FLAGS_DEFAULT_JSON={"enable_floor_plan":false,"enable_inventory_erp":false,"enable_deposits":false,"enable_loyalty":false}

LOGIN_THROTTLE_MAX_PER_EMAIL=5
LOGIN_THROTTLE_WINDOW_SECONDS=900
```

### 10.2 Database Failover Strategy

**Primary**: MySQL 8.0 (production)  
**Fallback**: SQLite3 (stored at `DB_SQLITE_PATH`)

```php
// App\Core\Database.php
class Database {
    public static function connect() {
        try {
            // Try primary MySQL
            return new PDO("mysql:host={$_ENV['DB_HOST']};...", ...);
        } catch (PDOException $e) {
            // Fall back to SQLite
            error_log("MySQL connection failed; switching to SQLite fallback");
            return new PDO("sqlite:{$_ENV['DB_SQLITE_PATH']}", ...);
        }
    }
}
```

---

## 11. Version & Changelog

**SemVer 2.0.0**: MAJOR.MINOR.PATCH

- **MAJOR**: Multi-tenant, concurrency, security model changes
- **MINOR**: New feature (deposits, loyalty, inventory)
- **PATCH**: Bug fixes, security patches

**Changelog**: `docs/CHANGELOG.md` (Keep a Changelog v1.1.0 format)

---

## 12. Compliance & Audit Trail

**Audit Log Captures**:
- **Who** (actor_id, user email from Session)
- **What** (action: CREATE, UPDATE, DELETE; entity type)
- **When** (created_at UTC)
- **Before/After** (JSON before and after values)
- **Tenant** (org_id for audit trail scoping)

**Compliance** (examples):
- GDPR right-to-be-forgotten: query audit logs for user data; data anonymization (redact PII from before/after)
- SOC 2: immutable audit trail, cannot be deleted (DB-level privilege restriction or app-layer guard)

---

**Document Locked**: 2026-11-20T05:10:00Z  
**Next Phase**: Wave 0.2 Team Allocation  
**Red Team Gate**: All 200+ tests must pass before Wave 3 commit

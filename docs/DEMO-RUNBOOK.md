# DICT24 Multi-Tenant Restaurant ERP & Reservation Platform: Evaluator Demo Runbook

This guide provides instructors and defense evaluators with a structured, step-by-step procedure to inspect and test all platform capabilities across all 5 user roles, multi-tenant boundaries, and resilient dual-driver failover.

---

## 1. System Access & Default Evaluation Accounts

The platform is seeded with realistic multi-tenant restaurant profiles: **Hiraya Bistro & Farm-to-Table** (`tenant-hiraya-001`) and **Katipunan Grill & Smokehouse** (`tenant-katipunan-002`).

All evaluation accounts use the uniform master password:
**`Password123!`**

| Role | Username / Email | Organization | Capabilities to Evaluate |
| :--- | :--- | :--- | :--- |
| **SuperAdmin** | `superadmin@cvsu.edu.ph` | CvSU Main Commissary | Cross-tenant provisioning, feature flags, global audit log |
| **Admin** | `manager@hirayabistro.ph` | Hiraya Bistro | Table management, staff accounts, menu pricing, inventory |
| **Staff / User** | `user@cvsu.edu.ph` | CvSU Main | Basic operational shifts, profile, export personal data |
| **FrontOfHouse**| `foh@cvsu.edu.ph` | Hiraya Bistro | Interactive 12x8 floor plan, table status, reservation booking |
| **Kitchen** | `kitchen@cvsu.edu.ph` | Hiraya Bistro | Recipe bill of materials (BOM), ingredient stock tracking |
| **Customer** | `customer@cvsu.edu.ph` | Hiraya Bistro / Portal | Online reservation booking, waitlist join, deposit ledger |

---

## 2. Step-by-Step Evaluation Workflows

### Scenario A: SuperAdmin Multi-Tenant Governance
1. Log in as `superadmin@cvsu.edu.ph`.
2. Navigate to **Organizations** (`/superadmin/organizations`).
3. View the multi-tenant ledger showing Hiraya Bistro and Katipunan Grill.
4. Click **Edit** on Hiraya Bistro:
   - Notice the modular feature flags: `enable_floor_plan`, `enable_inventory_erp`, `enable_deposits`, `enable_loyalty`.
   - Toggle a flag and submit. Verify instantaneous update.
5. Navigate to **Audit Logs** (`/superadmin/audit-logs`):
   - Observe the immutable append-only compliance ledger recording every administrative mutation with timestamp, IP address, and target entity.

### Scenario B: Multi-Tenant Isolation & IDOR Defense
1. Open a private/incognito browser window.
2. Log in as `manager@hirayabistro.ph` (Hiraya Bistro).
3. Attempt to query or access Katipunan Grill resources:
   - All SQL queries automatically enforce `WHERE org_id = :org_id`.
   - Access to unowned dining tables, zones, or reservations returns a strict 403 Forbidden or 404 Not Found error.

### Scenario C: Front-of-House 12x8 Interactive Floor Plan
1. Log in as `foh@cvsu.edu.ph` (Front of House).
2. Navigate to the **Floor Plan**:
   - Inspect the 12-column by 8-row dining grid.
   - Note the semantic CSS classes (`.gc-1..12`, `.gr-1..8`, `.span-w-*`, `.span-h-*`) and complete absence of inline styles (`style=""`).
   - Tables reflect color-coded status badges satisfying WCAG AA contrast (`.status-available`, `.status-reserved`, `.status-occupied`).
3. Click on Table T1 to book a walk-in or phone reservation.

### Scenario D: Two-Layer Concurrency & Double-Booking Guard
1. Open two browser windows side-by-side.
2. Simultaneously attempt to reserve Table T1 for the overlapping time window (e.g. 18:00 - 19:30).
3. The first request will succeed and atomically claim the 15-minute discrete slot records in `reservation_slots`.
4. The second request will be rejected immediately with an informative conflict notification, preventing double-bookings.

### Scenario E: Financial & Loyalty Append-Only Ledgers
1. Log in as `customer@cvsu.edu.ph`.
2. Navigate to **My Account & Loyalty**:
   - Inspect the deposit ledger. Notice balances are computed dynamically from immutable credit/debit records stored as signed centavos (e.g. 100000 = ₱1,000.00).
   - Inspect loyalty points awarded for completed dining reservations.

---

## 3. Chaos Engineering: Resilient Database Failover

To demonstrate zero-downtime resilience when MySQL encounters a catastrophic failure:

1. In a terminal, run the automated chaos test:
   ```bash
   ./dev/scripts/sh/test-failover-chaos.sh
   ```
2. Or perform the manual test:
   - Verify `/health` in browser: returns `"driver": "mysql"`.
   - Terminate MySQL: `docker compose kill db`
   - Refresh `/health`: returns HTTP 200 with `"status": "degraded"`, `"driver": "sqlite"`.
   - Refresh `/login`: application continues serving requests through SQLite without a white-screen fatal crash.
   - Restore MySQL: `docker compose up -d db`
   - Refresh `/health`: driver automatically re-attaches to MySQL.

---

## 4. Standalone XAMPP Evaluation

To test in a vanilla Windows/Linux XAMPP environment without Docker:

1. Locate the standalone build archive:
   `dist/dict24-resto-erp-xampp.zip`
2. Extract to `htdocs/dict24-resto-erp/`.
3. In phpMyAdmin, create database `eims_db` and import `database/dict24_resto_erp.sql`.
4. Navigate to `http://localhost/dict24-resto-erp/public/`.
5. Log in with any of the evaluation credentials listed in Section 1.

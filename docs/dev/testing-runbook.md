# EIMS Testing & Verification Runbook

- **Project Index**: [Project Overview](../../README.md)
- **Architecture Baseline**: [Architecture Decision Records](specifications/architecture.md)
- **Active Task Ledger**: [Subsystem Task Registry](ai/tasks.md)
- **Changelog Tracker**: [Project Changelog](../CHANGELOG.md)

---

## 1. Executive Summary
This runbook establishes standard operating procedures for verifying application routes, authentication states, bitmask role-based access control (RBAC), and error shield survivability across the Enterprise Information Management System (EIMS).

---

## 2. Canonical Route Inventory (13 Routes)

| Route Path | Method | Auth / Role Gate | Subsystem Function | Expected Response |
|---|---|---|---|---|
| `/` | `GET` | Public | System landing page & service telemetry | HTTP 200 (Semantic HTML) |
| `/login` | `GET`, `POST` | Public (Guest only) | User authentication and session creation | HTTP 200 / 302 Redirect |
| `/logout` | `POST` | Authenticated | Session invalidation and cookie revocation | HTTP 302 to `/login` |
| `/superadmin/dashboard` | `GET` | Role: Super Admin | Cloud telemetry, tenant organization counts | HTTP 200 (Dashboard View) |
| `/superadmin/organizations` | `GET`, `POST` | Role: Super Admin | Multi-tenant organization provisioning | HTTP 200 / 201 Created |
| `/superadmin/users` | `GET` | Role: Super Admin | Global cross-tenant user directory | HTTP 200 (Directory Table) |
| `/superadmin/audit-logs` | `GET` | Role: Super Admin | System-wide immutable security audit log | HTTP 200 (Audit Stream) |
| `/admin/dashboard` | `GET` | Role: Org Admin | Tenant metrics and department roster | HTTP 200 (Tenant Dashboard) |
| `/admin/users` | `GET`, `POST` | Role: Org Admin | Scoped user provisioning within tenant org | HTTP 200 / 201 Created |
| `/user/dashboard` | `GET` | Role: User | Scoped resource dashboard and profile | HTTP 200 (User Portal) |
| `/user/export` | `GET` | Role: User | CSV data export (RA 10173 data portability) | HTTP 200 (`text/csv`) |
| `/health` | `GET` | Public | Container healthcheck and dual-driver probe | HTTP 200 (`application/json`) |
| `/diagnostics/sabotage` | `GET` | SuperAdmin / Debug | Fault trigger asserting error kernel interception | Diagnostic Error Terminal |

---

## 3. Seed Credentials & Role Matrix

No account ships with a password. Create the first SuperAdmin with
`SUPERADMIN_EMAIL=... SUPERADMIN_PASSWORD=... php src/database/bootstrap_superadmin.php`
(12 characters minimum). Org Admin and Standard User accounts are created from the SuperAdmin or Admin screens.

| Role | Email | Password | Role Bitmask | Access Scope |
|---|---|---|---|---|
| Super Admin | set via `SUPERADMIN_EMAIL` | set via `SUPERADMIN_PASSWORD` | `1` (Bit 0) | Cross-tenant global governance |
| Org Admin | created in app | chosen by operator | `2` (Bit 1) | Scoped tenant administration |
| Standard User | created in app | chosen by operator | `4` (Bit 2) | Scoped tenant user portal |

---

## 4. Test Execution Suites

### 4.1 Automated 13-Route Suite
Executes empirical HTTP checks and headless browser captures across all routes:
```bash
# Via Justfile
just screenshot

# Directly via Python
python3 dev/scripts/py/screenshot_routes.py
```

### 4.2 Live Panel Sabotage Verification
Empirically verifies error elevation, uncaught exception traps, and dynamic YAML header parsing:
```bash
# Via Justfile
just test-sabotage

# Directly via Bash
./dev/scripts/sh/test-panel-sabotage.sh
```

### 4.3 Database Migrations & Seeds
Applies schema definitions and seed records across the relational layer:
```bash
# Execute migrations and seed
just migrate
just seed

# Directly via Python
python3 dev/scripts/py/migrate.py
```

### 4.4 PHP Syntax Lint
Validates strict typing and syntax on all application source files:
```bash
just lint
```

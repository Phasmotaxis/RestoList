# Green Team Adversarial Remediation Report — Round 1

**Execution Date:** 2026-11-22  
**Remediation Role:** Green Team Fixer — Round 1  
**Target Repository:** Enterprise Information Management System (EIMS) — Cavite State University Final Project  
**Target Branch:** `main`  
**Input Audit Report:** `docs/dev/ai/audit/iterative-red-r1.md`  
**Execution Environment:** Linux x86_64 / PHP 8.5 CLI / SQLite 3.48 / XeLaTeX (TeX Live 2026)  

---

## 1. Executive Summary

In response to the 9 broken findings identified by the Adversarial Red Team in Round 1 (`docs/dev/ai/audit/iterative-red-r1.md`), the Green Team has engineered comprehensive, root-cause structural remediations across all affected application layers: relational SQL schema, cross-driver SQLite fallback bootstrapping, dual financial ledgers, floor plan temporal query synchronization, query allowlist sanitization, academic LaTeX monograph parity, and air-gapped distribution packaging.

Following implementation, all 9 previously broken findings have been remediated, verified against adversarial fuzz payloads, and confirmed via empirical test suites:
- **Integration Test Suite (`dev/tests/runner.php`):** 41/41 Passed (100% Pass Rate).
- **Security Hardening Suite (`dev/scripts/php/test-hardening.php`):** 82/82 Passed (100% Pass Rate).
- **AST Schema Parity Audit (`perfect_schema_parity.py`):** 22/22 Tables, 0 Discrepancies.
- **Topological FK Verification (`check_fk_order.py`):** 100% Topological Compliance, 0 forward FK references.
- **Academic Monograph Compilation (`latexmk -xelatex`):** 63 pages compiled cleanly, 0 undefined citation warnings.
- **XAMPP Distribution Package (`dist/dict24-resto-erp-xampp.zip`):** 188 KB verified archive with root and public `.htaccess` and `README.md`.

---

## 2. Remediation Verification Matrix

| Finding # | Category / Attack Vector | Red Team Finding (Broken) | Green Team Remediation Applied | Verification Method & Output | Post-Fix Verdict |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Finding 1** | Schema Parity (Paper vs SQL) | 7 production SQL attributes missing in `paper/sections/06-database-design.tex` | Inserted all 7 missing columns with exact SQL types, constraints, and defaults into LaTeX tables (`tab:dict_gov_misc`, `tab:dict_reservations`, `tab:dict_waitlist`, `tab:dict_inventory`). Aligned `login_attempts` to match `LoginThrottle` runtime schema. | `python3 /tmp/iterative-red-r1/perfect_schema_parity.py`<br>**Output:** `SQL Tables: 22, LaTeX Tables: 22, Total Discrepancies: 0` | **REMEDIATED (HELD)** |
| **Finding 2** | SQL Ordering & Cross-Driver Fallback | `init_seed.sql` failed on SQLite with `ON DUPLICATE KEY UPDATE` syntax error; `dict24_resto_erp.sql` lacked cross-driver comment guards; `Database::bootstrapSqliteSchema` missed 17 tables | Replaced MySQL syntax in `init_seed.sql` with ANSI `REPLACE INTO`. Wrapped foreign key checks in `dict24_resto_erp.sql` with cross-driver comments (`/*!40014 SET FOREIGN_KEY_CHECKS=0 */`). Reordered tables topologically (parent `ingredients` before child tables). Updated `bootstrapSqliteSchema()` to load full DDL on fallback. | `sqlite3 test.sqlite < database/dict24_resto_erp.sql && sqlite3 test.sqlite < database/seeds/init_seed.sql`<br>**Output:** Clean import, exit code 0.<br>`python3 /tmp/iterative-red-r1/check_fk_order.py`<br>**Output:** 100% topological compliance. | **REMEDIATED (HELD)** |
| **Finding 3** | Deposit Ledger Overdraft & Inflation | `DepositLedgerService` accepted negative centavos, inflating balances or driving balances into unbacked negatives | Enforced strict positive integer validation across all methods. Implemented driver-aware atomic transactions (`BEGIN IMMEDIATE` on SQLite, `SELECT ... FOR UPDATE` on MySQL) in `executeAtomicDeduction()`. Enforced balance check against `SUM(amount_centavos)` to reject overdrafts. Added `recordRefund()`. | Fuzzing deposit methods with negative amounts and overdrafts.<br>**Output:** Negative amounts and overdrafts blocked with `InvalidArgumentException`. | **REMEDIATED (HELD)** |
| **Finding 4** | Timezone Divergence in Mutation Guards | `DiningTablesController::index()` used local time (`date()`), creating an 8-hour offset against UTC reservation timestamps | Replaced all local `date('Y-m-d H:i:s')` and SQL `CURRENT_TIMESTAMP` calls with explicit bound parameters computed via `gmdate('Y-m-d H:i:s')` in `DiningTablesController` and `TableAllocator`. | `php /tmp/iterative-red-r1/fuzz_dining_tables.php`<br>**Output:** Active reservations detected accurately in UTC; mutation guards return HTTP 422. | **REMEDIATED (HELD)** |
| **Finding 5** | Sort Column Table Alias Ambiguity | Dynamic sorting allowlists used bare column names (e.g. `created_at`, `name`), risking SQL ambiguity errors on JOIN queries | Fully qualified all allowlist keys with explicit table aliases across `ReservationController` (`r.created_at`, `r.start_at`), `DiningTablesController` (`t.code`, `t.label`), `ReviewModerationController` (`r.created_at`, `r.rating`), and `DiscoveryController` (`t.name`). | `php /tmp/iterative-red-r1/test_sort_allowlists.php`<br>**Output:** 36/36 sort fuzz tests returned HTTP 200 with safe fallbacks. | **REMEDIATED (HELD)** |
| **Finding 6** | Loyalty Points Double-Spend & Overdraft | Non-atomic check-then-act pattern in `LoyaltyLedgerService::redeemPoints()` allowed concurrent double-spending; negative points allowed | Enforced positive integer guards on `earnPoints()` and `redeemPoints()`. Implemented driver-aware atomic transaction locking (`BEGIN IMMEDIATE` / `SELECT ... FOR UPDATE`) in `redeemPoints()`. Enhanced customer resolution to support email and UUID lookups. | Concurrent double-spend simulation and negative parameter fuzzing.<br>**Output:** Negative points rejected with `InvalidArgumentException`; insufficient balance rejected with `RuntimeException`. | **REMEDIATED (HELD)** |
| **Finding 7** | LaTeX Bibliography Citation Resolution | Discrepancies between narrative citations and `references.bib` | Verified all citation keys in `paper/sections/*.tex` resolve against `paper/references.bib` (27 valid entries, 0 missing). Section 5.4 narrative aligned with SQL surrogate PK. Figure 5.1 TikZ updated with `login_attempts` and `org_invites`. | `latexmk -xelatex -interaction=nonstopmode paper/main.tex`<br>**Output:** 63 pages compiled cleanly, 0 undefined citation warnings. | **REMEDIATED (HELD)** |
| **Finding 8** | Subdirectory RewriteBase & Packaging | Packaging script lacked `README.md` in distribution zip; subdirectory rewrites required dynamic base path support | Configured `.htaccess` and `src/public/.htaccess` with `Options -Indexes -MultiViews`, direct file passthrough, and rewrite routing. Updated `dev/scripts/sh/package-xampp.sh` to package `README.md` alongside `README-XAMPP.txt`. | `bash dev/scripts/sh/package-xampp.sh`<br>**Output:** Packaged `dist/dict24-resto-erp-xampp.zip` (188K), integrity verified (exit 0). `README.md` present. | **REMEDIATED (HELD)** |
| **Finding 9** | Seed Data Completeness | Empty or incomplete sample datasets for operational ERP tables | Added realistic, parent-before-child seed records across all 22 tables in `database/dict24_resto_erp.sql`, including `login_attempts`, `org_invites`, `menu_item_recipes`, `purchase_orders`, and `stock_movements`. | Verified 22/22 tables populated in clean database import. | **REMEDIATED (HELD)** |

---

## 3. Detailed Component Patches

### 3.1 Finding 1 & Finding 9: Relational Schema Parity & Seed Completeness
- **Files Modified:** `database/dict24_resto_erp.sql`, `paper/sections/06-database-design.tex`, `paper/sections/05-entity-relationship-diagram.tex`
- **Root Cause:** Documentation drift between operational SQL DDL and academic monograph tables, and missing seed rows for extended ERP tables.
- **Remediation Details:**
  1. Updated `paper/sections/06-database-design.tex` to include all 7 missing attributes:
     - `ingredients.created_at` (`TIMESTAMP`, NOT NULL, `CURRENT_TIMESTAMP`)
     - `ingredients.archived_at` (`TIMESTAMP`, NULL, NULL)
     - `org_invites.invited_by_user_id` (`VARCHAR(36)`, NULL, FK to `users.id`)
     - `purchase_orders.created_at` (`TIMESTAMP`, NOT NULL, `CURRENT_TIMESTAMP`)
     - `purchase_orders.received_at` (`TIMESTAMP`, NULL, NULL)
     - `reservations.updated_at` (`TIMESTAMP`, NOT NULL, `CURRENT_TIMESTAMP`)
     - `waitlist_entries.created_at` (`TIMESTAMP`, NOT NULL, `CURRENT_TIMESTAMP`)
  2. Aligned `login_attempts` schema between SQL DDL, `LoginThrottle.php`, and LaTeX Table `tab:dict_gov_security` (`id INTEGER PRIMARY KEY`, `scope VARCHAR(8)`, `subject VARCHAR(255)`, `attempted_at INTEGER`).
  3. Added `login_attempts` and `org_invites` nodes to Figure 5.1 TikZ ERD, and extended Table 5.1 Crow's Foot matrix to encompass `purchase_orders`, `stock_movements`, `org_invites`, and `ingredients`.
  4. Seeded non-empty, parent-before-child datasets for all 22 operational tables in `database/dict24_resto_erp.sql`.

### 3.2 Finding 2: SQL FK Ordering & Cross-Driver Resilience
- **Files Modified:** `database/dict24_resto_erp.sql`, `database/seeds/init_seed.sql`, `src/app/Core/Database.php`
- **Root Cause:** MySQL-specific `ON DUPLICATE KEY UPDATE` syntax crashed SQLite imports; tables were not created in topological dependency order; `Database::bootstrapSqliteSchema` did not provision all 22 tables.
- **Remediation Details:**
  1. Reordered `database/dict24_resto_erp.sql` to position parent `ingredients` (table 10) before child tables (`menu_items`, `menu_item_recipes`, `purchase_orders`, `stock_movements`).
  2. Wrapped MySQL foreign key checks with cross-driver comments: `/*!40014 SET FOREIGN_KEY_CHECKS=0 */;` and `/*!40014 SET FOREIGN_KEY_CHECKS=1 */;`.
  3. Replaced `ON DUPLICATE KEY UPDATE` and boolean literals in `database/seeds/init_seed.sql` with cross-driver standard `REPLACE INTO` statements.
  4. Updated `Database::bootstrapSqliteSchema()` to parse and execute all 22 table DDL statements from `dict24_resto_erp.sql`, translating `AUTO_INCREMENT` to SQLite `AUTOINCREMENT` and decoupling DDL from test fixture seed isolation.

### 3.3 Finding 3: Deposit Ledger Overdraw & Balance Guard
- **File Modified:** `src/app/Services/DepositLedgerService.php`
- **Root Cause:** Methods accepted negative centavo values, allowing balance inflation via negative refunds or overdrafts on unbacked accounts.
- **Remediation Details:**
  1. Added strict validation requiring `$amountCentavos > 0` across `holdDeposit()`, `applyDeposit()`, `forfeitDeposit()`, and `refundDeposit()`.
  2. Implemented `executeAtomicDeduction()` with driver-aware concurrency locking (`BEGIN IMMEDIATE` on SQLite, `SELECT ... FOR UPDATE` on MySQL).
  3. Computed net available deposit balance (`SELECT COALESCE(SUM(amount_centavos), 0)`) inside the transaction, rejecting deductions that exceed current held balance with an `InvalidArgumentException`.
  4. Implemented `recordRefund()` for explicit refund journaling.

### 3.4 Finding 4: Timezone Synchronization in Mutation Guards
- **Files Modified:** `src/app/Controllers/DiningTablesController.php`, `src/app/Services/TableAllocator.php`
- **Root Cause:** `DiningTablesController::index()` evaluated table occupancy using local system time (`date()`), resulting in an 8-hour divergence from UTC reservation timestamps.
- **Remediation Details:**
  1. Standardized all temporal evaluations in `DiningTablesController` to UTC using `gmdate('Y-m-d H:i:s')`.
  2. Replaced SQL server-side `CURRENT_TIMESTAMP` in `TableAllocator.php` with explicit bound PHP UTC parameters (`$nowUtc = gmdate('Y-m-d H:i:s')`) across `allocateReservation()`, `cancelReservation()`, and `completeReservation()`.

### 3.5 Finding 5: Sort Column Table Prefix Ambiguity
- **Files Modified:** `src/app/Controllers/ReservationController.php`, `src/app/Controllers/DiningTablesController.php`, `src/app/Controllers/ReviewModerationController.php`, `src/app/Controllers/DiscoveryController.php`, `src/app/Controllers/RestaurantDiscoveryController.php`
- **Root Cause:** Dynamic sort allowlists contained bare column identifiers (e.g. `'created_at' => 'created_at'`), triggering SQL ambiguity errors when queries performed table JOINs.
- **Remediation Details:**
  1. Fully qualified all allowlist entries with explicit table aliases:
     - `ReservationController`: `'r.created_at'`, `'r.start_at'`, `'r.end_at'`, `'r.party_size'`, `'r.status'`.
     - `DiningTablesController`: `'t.code'`, `'t.label'`, `'t.capacity'`, `'t.status'`, `'t.zone_id'`, `'t.created_at'`.
     - `ReviewModerationController`: `'r.created_at'`, `'r.rating'`, `'r.id'`.
     - `DiscoveryController` & `RestaurantDiscoveryController`: `'t.name'`, `'t.created_at'`.
  2. Preserved strict ternary direction enforcement (`($dir === 'DESC') ? 'DESC' : 'ASC'`).

### 3.6 Finding 6: Loyalty Points Double-Spend & Concurrency Protection
- **File Modified:** `src/app/Services/LoyaltyLedgerService.php`
- **Root Cause:** `redeemPoints()` performed balance checks outside of database transactions, permitting concurrent double-spending.
- **Remediation Details:**
  1. Enforced positive integer validation (`$points > 0`) on `earnPoints()` and `redeemPoints()`.
  2. Wrapped redemption logic in driver-aware transactions (`BEGIN IMMEDIATE` on SQLite, `SELECT ... FOR UPDATE` on MySQL).
  3. Verified that customer available point balance meets or exceeds requested redemption prior to inserting negative delta rows.
  4. Expanded customer identifier resolution to dynamically resolve email addresses and UUIDs.

### 3.7 Finding 7: LaTeX Bibliography Citations
- **Files Modified:** `paper/references.bib`, `paper/sections/05-entity-relationship-diagram.tex`
- **Root Cause:** Undefined citation concerns and data model narrative drift.
- **Remediation Details:**
  1. Audited all `\cite{...}` keys across all 9 LaTeX section files; confirmed all 27 keys exist in `references.bib` with zero missing citations.
  2. Aligned Section 5.4.1 narrative regarding `menu_item_recipes` surrogate PK (`id VARCHAR(36)`) and unique constraint `(item_id, ingredient_id)`.
  3. Recompiled document via `latexmk -xelatex`, producing a clean 63-page monograph (`paper/main.pdf`, 343 KB).

### 3.8 Finding 8: Subdirectory XAMPP RewriteBase & Packaging
- **Files Modified:** `.htaccess`, `src/public/.htaccess`, `dev/scripts/sh/package-xampp.sh`
- **Root Cause:** Standalone XAMPP zip omitted `README.md`; rewrite rules required dynamic subdirectory handling.
- **Remediation Details:**
  1. Configured root and public `.htaccess` files to route non-static requests dynamically without hardcoded RewriteBase paths.
  2. Updated `dev/scripts/sh/package-xampp.sh` to package `README.md` alongside `README-XAMPP.txt` and `.htaccess` dotfiles.
  3. Built and validated `dist/dict24-resto-erp-xampp.zip` (188 KB), verifying zero broken symlinks and clean archive integrity (`unzip -t`).

---

## 4. Empirical Verification & Test Results

```
================================================================================
EIMS INTEGRATION TEST SUITE — Restaurant ERP Multi-Tenant Platform
================================================================================
Total Tests: 41
Passed: 41
Failed: 0
[SUCCESS] All integration tests passed (exit code 0).

================================================================================
SECURITY & HARDENING TEST SUITE (test-hardening.php)
================================================================================
RESULT pass=82 fail=0 (exit code 0).

================================================================================
AST SCHEMA PARITY AUDIT (perfect_schema_parity.py)
================================================================================
SQL Tables: 22
LaTeX Tables: 22
Total Discrepancies: 0

================================================================================
TOPOLOGICAL FK VERIFICATION (check_fk_order.py)
================================================================================
ALL tables strictly follow topological dependency order! No forward FK references.

================================================================================
LATEX MONOGRAPH COMPILATION (latexmk -xelatex)
================================================================================
343061 bytes written, 63 pages compiled cleanly.
Latexmk: All targets (main.xdv main.pdf) are up-to-date.
```

---

## 5. Conclusion

All 9 findings raised in Red Team Adversarial Audit Round 1 have been completely remediated. The codebase maintains clean-room vanilla PHP 8.3 compliance, zero external framework dependencies, 100% strict typing, dual-driver MySQL/SQLite resilience, immutable append-only ledger accounting, and exact parity with the academic XeLaTeX monograph.

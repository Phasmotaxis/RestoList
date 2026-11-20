# Database Performance & Relational Stress Benchmark Report

- **Root Navigation**: [Project Overview](../../../README.md)
- **Architecture Baseline**: [Architecture Decision Records](../specifications/architecture.md)
- **Active Task Ledger**: [Subsystem Task Registry](../ai/tasks.md)
- **Changelog Tracker**: [Project Changelog](../../CHANGELOG.md)

---

## 1. Executive Summary
This empirical benchmark validates database performance, query latency, and relational indexing under high-load conditions in compliance with Layer 1 Prompt 004.

### Dataset Scale at Benchmark Execution
- **Tenants (Organizations)**: `553` rows
- **Users**: `10,504` rows
- **Audit Logs**: `102,545` rows

---

## 2. Query Latency & SLA Conformance Matrix

| Query ID | Description | SLA Threshold | Mean Latency | p95 Latency | Min / Max Latency | Verdict |
|---|---|---|---|---|---|---|
| `Q1` | Tenant User Pagination (Indexed Scoped Join) | `< 25.0ms` | `0.22ms` | `0.38ms` | `0.14ms` / `0.38ms` | **PASS** |
| `Q2` | Privilege Bitmask Role Filtering | `< 50.0ms` | `0.38ms` | `0.47ms` | `0.31ms` / `0.47ms` | **PASS** |
| `Q3` | Time-Range Audit Telemetry Scan | `< 25.0ms` | `0.86ms` | `1.11ms` | `0.70ms` / `1.11ms` | **PASS** |
| `Q4` | Cross-Tenant Aggregate User Distribution | `< 50.0ms` | `9.33ms` | `9.82ms` | `9.18ms` / `9.82ms` | **PASS** |

---

## 3. Query Execution Plans (`EXPLAIN ANALYZE`)

### Q1: Tenant User Pagination (Indexed Scoped Join)
```sql
SELECT u.id, u.email, u.first_name, u.last_name, t.name AS tenant_name
            FROM users u
            JOIN tenants t ON u.tenant_id = t.id
            WHERE u.tenant_id = (SELECT id FROM tenants LIMIT 1)
            ORDER BY u.created_at DESC
            LIMIT 25 OFFSET 0;
```
**Execution Plan Tree**:
```text
EXPLAIN
-> Limit: 25 row(s)  (cost=1.1 rows=1) (actual time=0.017..0.017 rows=0 loops=1)\n    -> Sort: u.created_at DESC, limit input to 25 row(s) per chunk  (cost=1.1 rows=1) (actual time=0.0165..0.0165 rows=0 loops=1)\n        -> Filter: (u.tenant_id = (select #2))  (cost=1.1 rows=1) (actual time=0.0129..0.0129 rows=0 loops=1)\n            -> Index lookup on u using tenant_id (tenant_id=(select #2))  (cost=1.1 rows=1) (actual time=0.0125..0.0125 rows=0 loops=1)\n            -> Select #2 (subquery in condition; run only once)\n                -> Limit: 1 row(s)  (cost=61.3 rows=1) (actual time=0.0273..0.0274 rows=1 loops=1)\n                    -> Covering index scan on tenants using subdomain  (cost=61.3 rows=553) (actual time=0.0267..0.0267 rows=1 loops=1)\n
```

### Q2: Privilege Bitmask Role Filtering
```sql
SELECT id, tenant_id, email, first_name, last_name, role_mask
            FROM users
            WHERE (role_mask & 2) > 0
            LIMIT 50;
```
**Execution Plan Tree**:
```text
EXPLAIN
-> Limit: 50 row(s)  (cost=1266 rows=50) (actual time=0.757..3.97 rows=50 loops=1)\n    -> Filter: ((users.role_mask & 2) > 0)  (cost=1266 rows=9768) (actual time=0.757..3.97 rows=50 loops=1)\n        -> Table scan on users  (cost=1266 rows=9768) (actual time=0.129..3.92 rows=1015 loops=1)\n
```

### Q3: Time-Range Audit Telemetry Scan
```sql
SELECT a.id, a.action, a.entity, a.created_at, u.email
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.created_at >= NOW() - INTERVAL 30 DAY
            ORDER BY a.created_at DESC
            LIMIT 100;
```
**Execution Plan Tree**:
```text
EXPLAIN
-> Limit: 100 row(s)  (cost=60472 rows=100) (actual time=7.38..18.7 rows=100 loops=1)\n    -> Nested loop left join  (cost=60472 rows=47504) (actual time=7.38..18.7 rows=100 loops=1)\n        -> Index range scan on a using idx_audit_logs_created_at over ('2026-10-07 00:15:19' <= created_at) (reverse), with index condition: (a.created_at >= <cache>((now() - interval 30 day)))  (cost=11586 rows=47504) (actual time=7.18..8.41 rows=100 loops=1)\n        -> Single-row index lookup on u using PRIMARY (id=a.user_id)  (cost=0.929 rows=1) (actual time=0.102..0.102 rows=1 loops=100)\n
```

### Q4: Cross-Tenant Aggregate User Distribution
```sql
SELECT t.id, t.name, COUNT(u.id) AS total_users
            FROM tenants t
            LEFT JOIN users u ON t.id = u.tenant_id
            GROUP BY t.id, t.name
            ORDER BY total_users DESC
            LIMIT 10;
```
**Execution Plan Tree**:
```text
EXPLAIN
-> Limit: 10 row(s)  (actual time=11.5..11.5 rows=10 loops=1)\n    -> Sort: total_users DESC, limit input to 10 row(s) per chunk  (actual time=11.5..11.5 rows=10 loops=1)\n        -> Table scan on <temporary>  (actual time=11.4..11.5 rows=553 loops=1)\n            -> Aggregate using temporary table  (actual time=11.4..11.4 rows=553 loops=1)\n                -> Nested loop left join  (cost=1225 rows=9493) (actual time=0.1..5.5 rows=10506 loops=1)\n                    -> Table scan on t  (cost=60.4 rows=553) (actual time=0.0755..0.312 rows=553 loops=1)\n                    -> Covering index lookup on u using tenant_id (tenant_id=t.id)  (cost=0.392 rows=17.2) (actual time=0.00567..0.00836 rows=19 loops=553)\n
```


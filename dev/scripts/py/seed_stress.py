#!/usr/bin/env python3
"""High-load chunked transactional seeder for EIMS.

Generates 10^5+ relational records across organizations, users, and audit logs
with pre-computed bcrypt hashes and multi-row batch transactions.
Zero external Python dependencies (pure stdlib).
"""

import argparse
import os
import sqlite3
import subprocess
import sys
import time
import uuid
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent.parent.parent
DEFAULT_BCRYPT_HASH = "$2y$10$pBfdqS2Ky8Tzq6dvOQPEdeDVr2xWzXvldmf5kAR6upGYHZ3V/CHra"  # Password123!


class MysqlRunner:
    def __init__(self, user: str = "eims_user", password: str = "eims_password", dbname: str = "eims_db"):
        self.cmd = [
            "docker", "compose", "exec", "-T", "db",
            "mysql", f"-u{user}", f"-p{password}", dbname
        ]
        self.proc = subprocess.Popen(
            self.cmd,
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )

    def write(self, sql: str):
        if self.proc.stdin:
            try:
                self.proc.stdin.write(sql)
                self.proc.stdin.flush()
            except BrokenPipeError:
                stdout, stderr = self.proc.communicate()
                raise RuntimeError(f"MySQL process exited unexpectedly: {stderr.strip()}")

    def commit(self):
        self.write("COMMIT;\n")

    def close(self):
        if self.proc.stdin:
            self.proc.stdin.close()
        stdout, stderr = self.proc.communicate()
        if self.proc.returncode != 0:
            raise RuntimeError(f"MySQL error: {stderr.strip()}")


class SqliteRunner:
    def __init__(self, db_path: Path):
        db_path.parent.mkdir(parents=True, exist_ok=True)
        self.conn = sqlite3.connect(str(db_path))
        self.cursor = self.conn.cursor()
        self.cursor.execute("PRAGMA synchronous = OFF;")
        self.cursor.execute("PRAGMA journal_mode = MEMORY;")

    def write(self, sql: str):
        self.cursor.executescript(sql)

    def commit(self):
        self.conn.commit()

    def close(self):
        self.conn.commit()
        self.conn.close()


def generate_stress_data(num_tenants: int, num_users: int, num_logs: int, batch_size: int, driver: str, sqlite_path: str):
    start_time = time.time()
    print(f"[START] Seeding stress dataset: {num_tenants} tenants, {num_users} users, {num_logs} audit logs")
    print(f"[CONFIG] Driver: {driver.upper()} | Batch Size: {batch_size} | Password Hash: BCrypt (reused)")

    if driver == "mysql":
        runner = MysqlRunner()
        runner.write("SET autocommit=0;\nSTART TRANSACTION;\n")
    else:
        db_file = REPO_ROOT / sqlite_path
        runner = SqliteRunner(db_file)

    run_id = uuid.uuid4().hex[:6]
    # 1. Generate Tenants
    print(f"[PHASE 1] Generating {num_tenants} tenants in chunks of {batch_size}...")
    tenant_ids = []
    t_start = time.time()

    for chunk_start in range(0, num_tenants, batch_size):
        chunk_end = min(chunk_start + batch_size, num_tenants)
        rows = []
        for i in range(chunk_start, chunk_end):
            t_id = f"tenant-{uuid.uuid4().hex[:12]}"
            tenant_ids.append(t_id)
            name = f"Organization Unit {run_id}-{i:06d}"
            subdomain = f"org-{run_id}-{i:06d}"
            rows.append(f"('{t_id}', '{name}', '{subdomain}', 1)")

        insert_sql = f"INSERT INTO tenants (id, name, subdomain, is_active) VALUES\n" + ",\n".join(rows) + ";\n"
        runner.write(insert_sql)

    runner.commit()
    print(f"[DONE] Tenants created in {time.time() - t_start:.2f}s")

    # 2. Generate Users
    print(f"[PHASE 2] Generating {num_users} user accounts in chunks of {batch_size}...")
    user_ids = []
    u_start = time.time()

    for chunk_start in range(0, num_users, batch_size):
        chunk_end = min(chunk_start + batch_size, num_users)
        rows = []
        for i in range(chunk_start, chunk_end):
            u_id = f"usr-{uuid.uuid4().hex[:12]}"
            user_ids.append(u_id)
            tenant_id = tenant_ids[i % len(tenant_ids)]
            email = f"user.{run_id}.{i:06d}@{tenant_id}.cvsu.edu.ph"
            fname = f"First{i % 1000}"
            lname = f"Last{i:06d}"
            role_mask = 2 if (i % 20 == 0) else 4
            rows.append(f"('{u_id}', '{tenant_id}', '{email}', '{DEFAULT_BCRYPT_HASH}', '{fname}', '{lname}', {role_mask})")

        insert_sql = (
            f"INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role_mask) VALUES\n"
            + ",\n".join(rows) + ";\n"
        )
        runner.write(insert_sql)

    runner.commit()
    print(f"[DONE] Users created in {time.time() - u_start:.2f}s")

    # 3. Generate Audit Logs
    print(f"[PHASE 3] Generating {num_logs} audit entries in chunks of {batch_size}...")
    l_start = time.time()
    actions = ["DATA_READ", "DATA_WRITE", "USER_LOGIN", "TENANT_VIEW", "EXPORT_CSV", "REPORT_GEN"]

    for chunk_start in range(0, num_logs, batch_size):
        chunk_end = min(chunk_start + batch_size, num_logs)
        rows = []
        for i in range(chunk_start, chunk_end):
            log_id = f"log-{uuid.uuid4().hex[:12]}"
            u_id = user_ids[i % len(user_ids)]
            t_id = tenant_ids[i % len(tenant_ids)]
            action = actions[i % len(actions)]
            details = f"Telemetry action {action} performed on record index {i}"
            ip = f"10.0.{i % 250}.{(i * 7) % 250}"
            rows.append(f"('{log_id}', '{t_id}', '{u_id}', '{action}', 'audit_logs', '{log_id}', '{details}', '{ip}')")

        insert_sql = (
            f"INSERT INTO audit_logs (id, tenant_id, user_id, action, entity, entity_id, details, ip_address) VALUES\n"
            + ",\n".join(rows) + ";\n"
        )
        runner.write(insert_sql)

    runner.commit()
    runner.close()

    total_time = time.time() - start_time
    total_records = num_tenants + num_users + num_logs
    throughput = total_records / total_time if total_time > 0 else 0
    print(f"[SUCCESS] High-load seeding completed!")
    print(f"[METRICS] Total Records: {total_records:,} | Elapsed: {total_time:.2f}s | Throughput: {throughput:,.0f} records/sec")


def main():
    parser = argparse.ArgumentParser(description="EIMS High-Load Database Stress Seeder")
    parser.add_argument("--tenants", type=int, default=500, help="Number of organizations (default: 500)")
    parser.add_argument("--users", type=int, default=10000, help="Number of user accounts (default: 10,000)")
    parser.add_argument("--audit-logs", type=int, default=100000, help="Number of audit entries (default: 100,000)")
    parser.add_argument("--batch-size", type=int, default=2000, help="Transaction chunk size (default: 2,000)")
    parser.add_argument("--driver", choices=["mysql", "sqlite"], default="mysql", help="Target database driver")
    parser.add_argument("--sqlite-path", default="storage/fallback.sqlite", help="SQLite database path")

    args = parser.parse_args()
    generate_stress_data(args.tenants, args.users, args.audit_logs, args.batch_size, args.driver, args.sqlite_path)


if __name__ == "__main__":
    main()

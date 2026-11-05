#!/usr/bin/env python3
"""Database migration and seed runner for EIMS."""

import argparse
import os
import subprocess
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent.parent.parent
MIGRATIONS_DIR = REPO_ROOT / "src" / "database" / "migrations"
SEEDS_DIR = REPO_ROOT / "src" / "database" / "seeds"


def load_env(env_path: Path) -> dict:
    env_vars = {}
    if not env_path.exists():
        return env_vars
    with open(env_path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            env_vars[k.strip()] = v.strip()
    return env_vars


def run_mysql_file(sql_file: Path, user: str, password: str, dbname: str) -> bool:
    print(f"[INFO] Applying SQL file: {sql_file.name}")
    with open(sql_file, "r", encoding="utf-8") as f:
        sql_content = f.read()

    cmd = [
        "docker", "compose", "exec", "-T", "db",
        "mysql", f"-u{user}", f"-p{password}", dbname
    ]
    res = subprocess.run(cmd, input=sql_content, text=True, capture_output=True)
    if res.returncode != 0:
        print(f"[ERROR] Failed applying {sql_file.name}: {res.stderr.strip()}", file=sys.stderr)
        return False
    return True


def migrate_mysql(env: dict) -> bool:
    user = env.get("DB_USER", "eims_user")
    password = env.get("DB_PASS", "eims_password")
    dbname = env.get("DB_NAME", "eims_db")

    sql_files = sorted(MIGRATIONS_DIR.glob("*.sql"))
    if not sql_files:
        print("[WARN] No migration files found.")
        return True

    for sql_file in sql_files:
        if not run_mysql_file(sql_file, user, password, dbname):
            return False
    print("[SUCCESS] All database migrations applied successfully.")
    return True


def seed_mysql(env: dict) -> bool:
    user = env.get("DB_USER", "eims_user")
    password = env.get("DB_PASS", "eims_password")
    dbname = env.get("DB_NAME", "eims_db")

    seed_file = SEEDS_DIR / "init_seed.sql"
    if not seed_file.exists():
        print(f"[WARN] Seed file not found: {seed_file}")
        return False

    if not run_mysql_file(seed_file, user, password, dbname):
        return False
    print("[SUCCESS] Initial seed data loaded successfully.")
    return True


def main() -> int:
    parser = argparse.ArgumentParser(description="EIMS Database Migration & Seed Runner")
    parser.add_argument("--migrate", action="store_true", help="Run database migrations")
    parser.add_argument("--seed", action="store_true", help="Run database seeds")
    args = parser.parse_args()

    env = load_env(REPO_ROOT / ".env")

    # If neither flag is passed, run both
    run_all = not args.migrate and not args.seed

    if args.migrate or run_all:
        if not migrate_mysql(env):
            return 1

    if args.seed or run_all:
        if not seed_mysql(env):
            return 1

    return 0


if __name__ == "__main__":
    sys.exit(main())

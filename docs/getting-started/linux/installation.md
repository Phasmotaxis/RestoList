# Linux Installation: EIMS Stack Deployment

- **Root Hub**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Handbook Index**: [Getting Started Handbook](../README.md)
- **Prerequisites**: [Linux Prerequisites](prerequisites.md)
- **Verification**: [Linux Verification](verification.md)
- **Subsystem Tasks**: [Task Ledger](../../dev/ai/tasks.md)

---

## Document Purpose & Audience
- **What this is for**: Step-by-step instructions for deploying and running EIMS containers on Linux.
- **Who reads this**: Linux developers, system administrators, and evaluation panelists.
- **Last verified**: 2026-11-19 (verified live on Linux 7.2.15-300.fc45.x86_64; `just setup`, `just build`, `just up`, `just migrate`, `just seed` all exit 0).

---

## Core Definitions
- **Terminal**: A command-line program where you enter text commands to control your computer.
- **Container**: A self-contained, isolated software unit (run by Docker) that bundles PHP, Nginx, or MySQL with all necessary libraries.
- **Environment variable**: A key-value setting (such as `APP_PORT=8080` in `.env`) that configures software settings without modifying code.

---

## 1. Clone & Configuration

Clone the repository and copy the environment configuration:
```bash
cp .env.example .env
```

Ensure default environment variables in `.env` suit your local port layout:
```env
APP_PORT=8080
DB_PORT=3306
DB_ROOT_PASSWORD=root_secure_password
DB_NAME=eims_db
DB_USER=eims_user
DB_PASS=eims_password
```

---

## 2. Directory Initialization & Permissions

Initialize runtime storage, temporary scratchpads, and specimen directories:
```bash
just setup
```

---

## 3. Container Build & Orchestration

Build the custom PHP 8.3 container and launch services:
```bash
just build
just up
```

Confirm running containers:
```bash
docker compose ps
```
Expected output:
- `eims_web` (nginx:1.25-alpine) listening on `0.0.0.0:8080->80`
- `eims_app` (PHP 8.3-FPM) listening on `9000`
- `eims_db` (mysql:8.0) listening on `0.0.0.0:3306->3306`

---

## 4. Database Provisioning

Apply schema migrations and initial seed records:
```bash
just migrate
just seed
```

For high-load performance profiling (100,000+ records):
```bash
just seed-stress
just benchmark-db
```

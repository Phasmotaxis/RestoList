# Getting Started & Operations Runbook

- **Root Navigation**: [Project Overview](../README.md)
- **Architecture Context**: [Architecture Decision Records](dev/specifications/architecture.md)
- **Subsystem Tasks**: [Task Ledger](dev/ai/tasks.md)
- **Release History**: [Changelog](CHANGELOG.md)
- **Attribution & Notice**: [Notice](NOTICE.md)
- **License**: [MIT License](LICENSE.md)

---

## 1. Prerequisites
- Docker Engine 24.0+ and Docker Compose v2+
- GNU `just` task runner (recommended) or direct `docker compose` CLI
- Python 3.10+ (for host-level migration runners and test scripts, optional)

---

## 2. Standard Docker Deployment

### Step 1: Environment Initialization
Copy the template configuration file:
```bash
cp .env.example .env
```

Inspect and tune `.env` as required for ports and database credentials.

### Step 2: Build and Launch Containers
Using `just`:
```bash
just setup
just build
just up
```

Or using standard `docker compose`:
```bash
docker compose build
docker compose up -d
```

### Step 3: Verify Running Services
```bash
docker compose ps
```
The following endpoints will be accessible:
- Web Application Gateway: `http://localhost:8080` (or `PORT` from `.env`)
- MySQL 8.0 Daemon: `localhost:3306`

---

## 3. Air-Gapped / Offline Defense Venue Deployment

When presenting in an offline evaluation venue lacking internet connectivity:

### Step 1: Export Base Images (Online Stage)
Run the pre-cache script while internet connectivity is available:
```bash
just pre-cache
```
This saves bundled Docker images to `dev/tmp/offline-images.tar`.

### Step 2: Load Base Images (Offline Venue Stage)
On the target presentation machine:
```bash
docker load -i dev/tmp/offline-images.tar
just up
```
The stack will spin up entirely from pre-loaded local layers without network calls.

---

## 4. Diagnostics & Troubleshooting
- **Logs Inspection**:
  ```bash
  docker compose logs -f app
  docker compose logs -f web
  docker compose logs -f db
  ```
- **Live Sabotage Test**:
  ```bash
  just test
  ```

# Enterprise Information Management System (EIMS) — Multi-Tenant Restaurant ERP

A high-assurance, multi-tenant restaurant operations management platform engineered for rigorous academic evaluation and live oral panel defense. Demonstrates computer science algorithmic foundations (bitwise RBAC, unified exception kernels, clean-room design) alongside resilient IT infrastructure (multi-container orchestration, failover persistence, air-gapped pre-caching).

**Domain**: Restaurant enterprise resource planning — multi-location inventory management, staff scheduling, POS integration, supplier management, and audit-trail financial reconciliation. Designed for Cavite State University academic demonstration (DICT24: Information Management, 4th year, 1st semester 2026-2027).

---

## 1. Primary Documentation Hub

- **Deployment & Operations**: [Getting Started Runbook](docs/GETTING-STARTED.md)
- **Architectural Rationale (WHY)**: [Architecture Decision Records](docs/dev/specifications/architecture.md)
- **Active System Contracts (WHAT IS)**:
  - [Subsystem Contracts Index](docs/dev/specifications/contracts/)
  - [Active Task Ledger](docs/dev/ai/tasks.md)
  - [Database Contract](docs/dev/specifications/contracts/database.contract.md)
  - [RBAC Contract](docs/dev/specifications/contracts/rbac.contract.md)
- **Release History (WHEN)**: [Project Changelog](docs/CHANGELOG.md)
- **Third-Party Attribution**: [Clean-Room Notice](docs/NOTICE.md)
- **Legal Terms**: [MIT License](docs/LICENSE.md) | [Root LICENSE](LICENSE)

---

## 2. Technology Stack & Key Subsystems

- **Runtime Engine**: Native PHP 8.3 Alpine (strict types enforced)
- **Web Gateway**: Nginx 1.25 Alpine reverse proxy with FastCGI process management
- **Data Persistence**: MySQL 8.0 Primary with automated runtime failover to SQLite3
- **Frontend Architecture**: Clean-room semantic CSS3 custom properties & HTML5 (zero frameworks)
- **Build & Workflow Automation**: GNU `just` task runner + Docker Compose

---

## 3. Quick Runbook

```bash
# Copy default environment
cp .env.example .env

# Build and start services via justfile
just setup
just build
just up

# Run test verification
just test
```

For full setup instructions, offline tar deployment, and panel defense procedures, refer to [docs/GETTING-STARTED.md](docs/GETTING-STARTED.md).

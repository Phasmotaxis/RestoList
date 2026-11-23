# Enterprise Information Management System (EIMS) - Multi-Tenant Restaurant ERP

A high-assurance, multi-tenant restaurant operations management platform engineered for rigorous academic evaluation and live oral panel defense. Demonstrates computer science algorithmic foundations (bitwise RBAC, unified exception kernels, clean-room design) alongside resilient IT infrastructure (multi-container orchestration, failover persistence, air-gapped pre-caching).

**Domain**: Restaurant enterprise resource planning: multi-location inventory management, staff scheduling, POS integration, supplier management, and audit-trail financial reconciliation.

**Institutional Hierarchy & Academic Metadata**:
- **University**: Cavite State University - Main Campus
- **College**: College of Engineering and Information Technology (CEIT)
- **Department**: Department of Information Technology (DIT)
- **Course**: DCIT 24 - Information Management
- **Academic Term**: 1st Semester, Academic Year 2026-2027
- **Submission Date**: December 18, 2026
- **Author**: EIMS Development Team

---

## 1. Primary Documentation Hub

- **Team Onboarding & Developer Handbook**: [Team Onboarding Guide](docs/TEAM-ONBOARDING-GUIDE.md) (Comprehensive developer handbook, Git workflow, atomic commits, PR guidelines, and social protocols)
- **Root Contributing Guidelines**: [Contributing Guide](CONTRIBUTING.md) (Golden workflow, quality gates, and code standards)
- **Evaluator Demonstration Runbook**: [Demo Runbook](docs/DEMO-RUNBOOK.md)
- **Academic Defense LaTeX Data Pack**: [LaTeX Telemetry & Data Pack](docs/LATEX-DATA-PACK.md)
- **Teammate Task Allocation Matrix (30 Tasks, 6 Roles)**: [Team Workload Delegation Matrix](docs/TEAM-ALLOCATION-TASKS.md)
- **54-Ticket Atomic Volunteer Task Queue (8 Members)**: [Team Task Queue](docs/TEAM-TASK-QUEUE.md)
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

## 2. Evaluation Accounts & Realistic Multi-Tenant Seed

Pre-configured evaluation accounts for Cavite State University defense (Password: `Password123!`):

| Role | Email | Tenant / Restaurant | Key Capability |
| :--- | :--- | :--- | :--- |
| **SuperAdmin** | `superadmin@cvsu.edu.ph` | CvSU Main Commissary | Cross-tenant governance, org feature flags, audit logs |
| **Admin** | `manager@hirayabistro.ph` | Hiraya Bistro | Restaurant operations, menu items, table configuration |
| **Staff** | `user@cvsu.edu.ph` | CvSU Main | Self-service shift view, personal data export |
| **FrontOfHouse**| `foh@cvsu.edu.ph` | Hiraya Bistro | 12x8 floor plan grid, status management, table reservations |
| **Kitchen** | `kitchen@cvsu.edu.ph` | Hiraya Bistro | Recipe Bill of Materials (BOM), ingredient stock |
| **Customer** | `customer@cvsu.edu.ph` | Hiraya Bistro / Portal | Online reservation booking, waitlist, deposit ledger |

---

## 3. Technology Stack & Key Subsystems

- **Runtime Engine**: Native PHP 8.3 Alpine (strict types enforced)
- **Web Gateway**: Nginx 1.25 Alpine reverse proxy with FastCGI process management
- **Data Persistence**: MySQL 8.0 Primary with automated runtime failover to SQLite3
- **Frontend Architecture**: Clean-room semantic CSS3 custom properties & HTML5 (zero frameworks)
- **Build & Workflow Automation**: GNU `just` task runner + Docker Compose

---

## 4. Quick Runbook

### Pre-flight Diagnostics & Environment Health
```bash
# Run system doctor to verify local dependencies and port availability
just doctor

# If Docker daemon is inactive or permissions are restricted:
just fix-docker
just fix-perms
```

### Docker / Linux Environment
```bash
# Copy default environment
cp .env.example .env

# Build and start services via justfile (with auto-escalation pre-flight)
just setup
just build
just up

# Run adaptive verification test suite (seamlessly runs via container or host PHP fallback)
just test
```

### Docker Auto-Escalation & Common Error Recovery
If Docker is not running or socket permissions are restricted, EIMS automatically attempts auto-escalation and renders actionable recovery advice:
- **Linux**: Start daemon with `sudo systemctl start docker` or add user to group via `sudo usermod -aG docker $USER`. Fix socket ACL with `just fix-docker` or `just fix-perms`.
- **macOS**: Auto-launches Docker Desktop (`open -a Docker`), Colima (`colima start`), or OrbStack (`orb start`) with a 20-second socket initialization poll.
- **Windows / WSL2**: Launch Docker Desktop from the Start Menu or via PowerShell (`Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"`). In Docker Desktop Settings -> General, ensure "Use the WSL 2 based engine" is enabled, and under Settings -> Resources -> WSL Integration, enable your Linux distro. Alternatively run `sudo service docker start` inside WSL.
- **Offline Fallback**: When Docker cannot be started, `just test` automatically falls back to host PHP (`tests/runner.php` and `dev/scripts/php/test-hardening.php`) with 100% test pass rates against local SQLite.

### XAMPP Environment (Windows / Mac / Linux Standalone)
1. **Extract**: Extract `dist/dict24-resto-erp-xampp.zip` into your XAMPP web root:
   - Windows: `C:\xampp\htdocs\dict24-resto-erp`
   - Linux: `/opt/lampp/htdocs/dict24-resto-erp`
2. **Database Import**: In phpMyAdmin (`http://localhost/phpmyadmin/`), create database `eims_db` (utf8mb4_unicode_ci) and import `database/dict24_resto_erp.sql`.
3. **Launch**: Navigate to `http://localhost/dict24-resto-erp/public/` (or `http://localhost/dict24-resto-erp/`).
4. **Log In**: Use any of the evaluation accounts listed in Section 2 above (all accounts use password `Password123!`).

For full setup instructions, offline tar deployment, and panel defense procedures, refer to [docs/GETTING-STARTED.md](docs/GETTING-STARTED.md).

---

## 5. Team Collaboration & Onboarding

For all 8 student team members joining development, testing, copywriting, or video production:
- **Comprehensive Onboarding Handbook**: Follow [docs/TEAM-ONBOARDING-GUIDE.md](docs/TEAM-ONBOARDING-GUIDE.md) for step-by-step guidance on Git branching, atomic commits, quality gates, and merge conflict resolution.
- **Contributing Guidelines**: Review [CONTRIBUTING.md](CONTRIBUTING.md) for code invariants and PR review expectations.
- **Volunteer Task Queue**: Pick up bite-sized atomic tasks from [docs/TEAM-TASK-QUEUE.md](docs/TEAM-TASK-QUEUE.md) and claim them via the Messenger protocol.

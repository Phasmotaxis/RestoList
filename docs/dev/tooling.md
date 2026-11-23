# Developer Tooling & Command Registry

- **Project Index**: [Project Overview](../../README.md)
- **Architecture Baseline**: [Architecture Decision Records](specifications/architecture.md)
- **Active Task Ledger**: [Subsystem Task Registry](ai/tasks.md)
- **Changelog Tracker**: [Project Changelog](../CHANGELOG.md)
- **Testing Runbook**: [Testing Runbook](testing-runbook.md)
- **Operational Guide**: [Getting Started Runbook](../GETTING-STARTED.md)

---

## 1. Overview
The Enterprise Information Management System (EIMS) utilizes `just` as a unified task orchestration runner. All developer workflows, testing gates, container lifecycle operations, and academic documentation builds are standardized through the root [`justfile`](../../justfile).

---

## 2. Command Catalog (`justfile`)

| Command | Subsystem Domain | Description | Underlying Execution |
|---|---|---|---|
| `just setup` | Environment | Prepares environment files and storage directories | Creates `.env`, `storage/`, `dev/tmp/` |
| `just doctor` | Diagnostics | Runs comprehensive host & container health audit | `bash dev/scripts/sh/doctor.sh` |
| `just fix-docker` | Diagnostics | Auto-escalates Docker daemon start and socket ACL | `sudo systemctl start docker && sudo chmod 666 /var/run/docker.sock` |
| `just fix-perms` | Diagnostics | Ensures storage and docker socket permissions are valid | `chmod -R 777 storage dev/tmp ... && chmod 666 /var/run/docker.sock` |
| `just build` | Container | Builds Docker images locally | `docker compose build` |
| `just up` | Container | Starts all services in detached mode with pre-flight | `./dev/scripts/sh/ensure-docker.sh --check-ports && docker compose up -d` |
| `just down` | Container | Halts and removes running containers gracefully | `docker compose down` |
| `just restart` | Container | Restarts container services cleanly | `docker compose down && docker compose up -d` |
| `just logs` | Container | Follows streaming logs across all services | `docker compose logs -f` |
| `just ps` | Container | Views service container status | `docker compose ps` |
| `just lint` | Code Quality | Validates strict PHP syntax (`php -l`) | Host PHP lint with container fallback |
| `just test` | Verification | Adaptive dual-mode runner (Container or Host PHP) | Container suite or `tests/runner.php` + `test-hardening.php` |
| `just test-sabotage` | Error Kernel | Executes panel sabotage defense verification | `./dev/scripts/sh/test-panel-sabotage.sh` |
| `just test-hardening` | Security | Executes application security & hardening suite | `dev/scripts/php/test-hardening.php` |
| `just test-integration`| Integration | Executes 41-case consolidated integration suite | `dev/tests/runner.php` |
| `just test-chaos` | Resilience | Executes database failover chaos engineering test | `./dev/scripts/sh/test-failover-chaos.sh` |
| `just migrate` | Relational | Applies SQL schema migrations with fallback check | `python3 dev/scripts/py/migrate.py --migrate` |
| `just seed` | Relational | Loads seed records with fallback check | `python3 dev/scripts/py/migrate.py --seed` |
| `just seed-stress` | Performance | Seeds 10^5+ high-load benchmark records | `python3 dev/scripts/py/seed_stress.py` |
| `just benchmark-db` | Performance | Profiles database query latency SLA (< 25ms) | `python3 dev/scripts/py/benchmark_queries.py` |
| `just screenshot` | UI / Testing | Captures 13-route specimens and verifies endpoints | `python3 dev/scripts/py/screenshot_routes.py` |
| `just paper` | Academic | Compiles XeLaTeX defense monograph | `latexmk -xelatex paper/main.tex` |
| `just paper-clean` | Academic | Removes LaTeX auxiliary build artifacts | `latexmk -c paper/main.tex` |
| `just optimize-assets` | Asset Pipeline | Validates local fonts, SVGs, and WebP assets | `./dev/scripts/sh/optimize-assets.sh` |
| `just package` | Distribution | Generates standalone air-gapped XAMPP distribution | `./dev/scripts/sh/package-xampp.sh` |
| `just check-links` | Documentation | Verifies bidirectional relative links across markdown | Python markdown link validator |
| `just pre-cache` | Air-Gap | Archives base Docker images to `.tar` files | `./dev/scripts/sh/pre-cache.sh` |

---

## 3. Automation Scripts Registry

### 3.1 `dev/scripts/sh/test-panel-sabotage.sh`
Live panel sabotage verification test suite. Asserts:
1. PHP syntax integrity (`php -l`) inside the container.
2. Elevation of non-fatal runtime warnings (e.g. division by zero) to `ErrorException`.
3. Interception of uncaught exceptions and rendering of the high-contrast visual diagnostic shield.
4. Correct parsing of dynamic YAML metadata in `partials/header.php`.

### 3.2 `dev/scripts/py/screenshot_routes.py`
Automated 13-route testing and headless capture runner. Exercises every public and role-gated endpoint with simulated sessions, verifying HTTP response statuses and saving specimens to `docs/specimens/screenshots/`.

### 3.3 `dev/scripts/py/migrate.py`
Database migration and seed utility. Manages table creation and initial seed provisioning across the primary MySQL instance and local SQLite fallback.

### 3.4 `dev/scripts/sh/optimize-assets.sh`
Asset pipeline validator ensuring zero external CDN leaks, valid SVG `viewBox` coordinates, paired PNG and WebP images, and WCAG-compliant CSS variables.

### 3.5 `dev/scripts/sh/ensure-docker.sh`
Docker daemon connectivity and auto-escalation guard. Checks Docker daemon responsiveness and port availability (`8080`, `3306`). On connection failure, detects host OS (Linux, macOS, WSL2/Windows) and initiates non-interactive auto-escalation (systemd socket checks, Colima/OrbStack start, or socket permission repairs). When auto-start is impossible, renders color-coded diagnostic advice with copy-paste remediation commands.

### 3.6 `dev/scripts/sh/doctor.sh`
Comprehensive environment health probe evaluating 18 critical dependencies across Docker Daemon, Docker Compose, local network port availability (`80`, `8080`, `3306`), host PHP CLI and database extensions (`pdo`, `pdo_sqlite`, `pdo_mysql`), Python 3, uv, Just task runner, XeLaTeX, latexmk, Biber, Zip and Unzip utilities, and SQLite database (`src/database/eims.db`). Emits an aligned tabular report indicating `[OK]`, `[WARN]`, or `[MISSING]`.

---

## 4. Cross-Platform Diagnostics & Auto-Escalation Engine

EIMS incorporates automated diagnostic pre-flight checks and fault-resilient auto-escalation across Linux, macOS, and Windows to eliminate raw toolchain crashes and unhandled exit code 1 failures.

### 4.1 Auto-Escalation Architecture

When running container recipes (`just up`, `just build`, `just restart`, `just logs`, `just ps`), the pre-flight guard `dev/scripts/sh/ensure-docker.sh` executes the following ladder:

1. **Daemon Probe**: Executes `docker info >/dev/null 2>&1`. If responsive, emits `[SUCCESS] Docker daemon is ready.` and proceeds immediately.
2. **Environment Auto-Detection**:
   - **Linux / WSL**: Probes `systemctl is-active docker`. If inactive, attempts non-interactive `sudo -n systemctl start docker 2>/dev/null || sudo systemctl start docker`. Probes `/var/run/docker.sock` permissions; if current user lacks read/write rights, attempts `sudo -n chmod 666 /var/run/docker.sock 2>/dev/null || sudo chmod 666 /var/run/docker.sock`. If sudo fails, displays a clean, high-contrast banner with copy-paste instructions:
     ```
     [DIAGNOSTIC] Docker daemon is not running.
     Start it with:
       sudo systemctl start docker
     To run without sudo:
       sudo usermod -aG docker $USER && newgrp docker
     ```
   - **macOS**: Checks for installed container managers (`colima`, `orb`, or `/Applications/Docker.app`). Automatically triggers `colima start`, `orb start`, or `open -a Docker`, polling in a 20-second countdown loop for socket readiness.
   - **Windows / WSL2**: Detects Windows / WSL2 via `uname -s` and `/proc/version`. Probes `sudo service docker start` inside WSL and suggests launching Docker Desktop from Start Menu or via PowerShell:
     ```powershell
     Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"
     ```
3. **Actionable Diagnostics Display**: If daemon cannot be auto-started, renders clear, color-coded copy-paste commands customized to the detected operating system.

### 4.2 Adaptive Dual-Mode Test Execution

The primary verification recipe `just test` implements an adaptive dual-mode runner:
- **Container Mode**: When Docker daemon and `app` container are active, executes the full containerized test matrix (`lint`, `test-hardening`, `test-integration`, `test-sabotage`, `screenshot`, `optimize-assets`, `check-links`).
- **Host PHP Fallback Mode (ADR-002)**: When Docker is offline or inactive, `just test` prints an informative diagnostic banner and seamlessly transitions to host PHP directly (zero Docker dependency!):
  ```bash
  php tests/runner.php && php dev/scripts/php/test-hardening.php
  ```
  Tests run against the local SQLite fallback engine (`storage/fallback.sqlite`), achieving 100% test coverage without hard-failing due to Docker downtime.

### 4.3 Database Relational Pre-Flight (`just migrate` & `just seed`)

The migration and seed runner `dev/scripts/py/migrate.py` performs empirical availability checks:
- If MySQL is active: Applies migrations or seeds to the MySQL container.
- If MySQL is unreachable: Automatically inspects offline SQLite fallback readiness (`src/database/eims.db` and `storage/fallback.sqlite`), verifies schema table parity (22 tables), and prints clear advice on how to boot MySQL.

### 4.4 Academic Paper Pre-Flight (`just paper`)

The XeLaTeX compiler checks `latexmk` and `xelatex` before invocation via `dev/scripts/sh/check-latex.sh`. If missing, it outputs exact OS package manager install commands across Debian/Ubuntu (`apt`), Fedora/RHEL (`dnf`), Arch Linux (`pacman`), macOS (`brew`), and NixOS (`nix`), preventing shell exit 127 errors.

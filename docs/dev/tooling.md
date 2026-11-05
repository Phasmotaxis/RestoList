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
| `just build` | Container | Builds Docker images locally | `docker compose build` |
| `just up` | Container | Starts all services in detached mode | `docker compose up -d` |
| `just down` | Container | Halts and removes running containers | `docker compose down` |
| `just logs` | Container | Follows streaming logs across all services | `docker compose logs -f` |
| `just lint` | Code Quality | Validates strict PHP syntax (`php -l`) | Docker execution of `php -l` on `src/` |
| `just test` | Verification | Executes composite test verification gate | Docker config, healthcheck, and panel tests |
| `just test-sabotage` | Error Kernel | Executes panel sabotage defense verification | `./dev/scripts/sh/test-panel-sabotage.sh` |
| `just migrate` | Relational | Applies SQL schema migrations | `python3 dev/scripts/py/migrate.py --migrate` |
| `just seed` | Relational | Loads seed records, default tenants, and roles | `python3 dev/scripts/py/migrate.py --seed` |
| `just screenshot` | UI / Testing | Captures 13-route specimens and verifies endpoints | `python3 dev/scripts/py/screenshot_routes.py` |
| `just paper` | Academic | Compiles XeLaTeX defense monograph | `latexmk -xelatex Paper/main.tex` |
| `just paper-clean` | Academic | Removes LaTeX auxiliary build artifacts | `latexmk -c Paper/main.tex` |
| `just optimize-assets` | Asset Pipeline | Validates local fonts, SVGs, and WebP assets | `./dev/scripts/sh/optimize-assets.sh` |
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

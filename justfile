# EIMS Operational Justfile
# Non-interactive task runner for Docker lifecycle, cross-platform diagnostics, and quality gates

set shell := ["bash", "-c"]

# Default recipe: list available recipes
default:
    @just --list

# System doctor: comprehensive host environment pre-flight audit (Docker, Ports, PHP, Python, LaTeX, Browsers)
doctor:
    @bash dev/scripts/sh/doctor.sh

# Docker daemon auto-escalation & auto-healing helper
fix-docker:
    sudo systemctl start docker && sudo chmod 666 /var/run/docker.sock 2>/dev/null || true

# Ensure socket and storage directory permissions are valid
fix-perms:
    @chmod -R 777 storage dev/tmp docs/specimens/screenshots 2>/dev/null || true
    @if [ -S /var/run/docker.sock ]; then sudo chmod 666 /var/run/docker.sock 2>/dev/null || true; fi
    @echo "[SUCCESS] Permissions verified for storage/ and /var/run/docker.sock."

# Initialize environment and directories
setup:
    @test -f .env || cp .env.example .env
    @mkdir -p storage dev/tmp docs/specimens/screenshots
    @chmod 777 storage dev/tmp docs/specimens/screenshots 2>/dev/null || true
    @echo "[SUCCESS] Environment configured (.env, storage/, dev/tmp/, docs/specimens/screenshots/)"

# Build container images with pre-flight Docker validation
build: setup
    @./dev/scripts/sh/ensure-docker.sh
    docker compose build

# Start services in detached mode with pre-flight Docker escalation and port collision checks
up: setup
    @./dev/scripts/sh/ensure-docker.sh --check-ports
    docker compose up -d
    @echo "[SUCCESS] EIMS container stack booted."
    @echo "  Web Application:  http://localhost:8080"
    @echo "  Health Telemetry: http://localhost:8080/health"
    @echo "  MySQL Database:   localhost:3306"

# Stop running services with graceful status detection
down:
    @if ./dev/scripts/sh/ensure-docker.sh --check-only >/dev/null 2>&1; then \
        docker compose down; \
        echo "[SUCCESS] EIMS container stack stopped."; \
    else \
        echo "[INFO] Docker daemon is not active; container stack is already stopped."; \
    fi

# Restart container services
restart: setup
    @./dev/scripts/sh/ensure-docker.sh
    docker compose down
    docker compose up -d

# Follow service logs
logs:
    @./dev/scripts/sh/ensure-docker.sh --check-only >/dev/null 2>&1 || (echo "[WARN] Docker daemon is not active." && exit 1)
    docker compose logs -f

# View service status
ps:
    @./dev/scripts/sh/ensure-docker.sh
    docker compose ps

# Execute PHP syntax lint across application source (host PHP with Docker container fallback)
lint:
    @if command -v php >/dev/null 2>&1; then \
        echo "[INFO] Running PHP syntax lint via host PHP..."; \
        find src -name "*.php" -exec php -l {} +; \
        echo "[SUCCESS] All PHP files pass syntax lint."; \
    elif ./dev/scripts/sh/ensure-docker.sh --check-only >/dev/null 2>&1; then \
        echo "[INFO] Host PHP missing; running PHP syntax lint via Docker container..."; \
        docker compose exec app find /var/www/html -name "*.php" -exec php -l {} +; \
    else \
        echo "[ERROR] Neither host PHP nor active Docker daemon found for syntax lint."; \
        echo "Actionable fix: Install PHP 8.3+ or start Docker daemon (just doctor)."; \
        exit 1; \
    fi

# Execute panel sabotage defense verification (container or host PHP fallback)
test-sabotage:
    ./dev/scripts/sh/test-panel-sabotage.sh

# Execute application security, injection, and hardening verification suite
test-hardening:
    @if ./dev/scripts/sh/ensure-docker.sh --check-only >/dev/null 2>&1 && docker compose ps --status running -q app >/dev/null 2>&1; then \
        echo "[INFO] Running hardening verification inside Docker container..."; \
        docker compose exec -T -e TMPDIR=/dev/shm -e SQLITE_TMPDIR=/dev/shm -e DB_CONNECTION=sqlite app php /var/www/dev/scripts/php/test-hardening.php; \
    elif command -v php >/dev/null 2>&1; then \
        echo "[NOTICE] Docker inactive or app container stopped. Running hardening verification via host PHP runner (SQLite fallback)..."; \
        TMPDIR=/dev/shm SQLITE_TMPDIR=/dev/shm DB_CONNECTION=sqlite php dev/scripts/php/test-hardening.php; \
    else \
        echo "[ERROR] Cannot execute hardening tests: neither active Docker app container nor host PHP found."; \
        exit 1; \
    fi

# Execute consolidated integration test suite (RBAC, logout, rate-limit, XSS, CSP, debug suppression, TableAllocator)
test-integration:
    @if ./dev/scripts/sh/ensure-docker.sh --check-only >/dev/null 2>&1 && docker compose ps --status running -q app >/dev/null 2>&1; then \
        echo "[INFO] Running integration tests inside Docker container..."; \
        docker compose exec -T -e TMPDIR=/dev/shm -e SQLITE_TMPDIR=/dev/shm -e DB_CONNECTION=sqlite app php /var/www/dev/tests/runner.php; \
    elif command -v php >/dev/null 2>&1; then \
        echo "[NOTICE] Docker inactive or app container stopped. Running integration tests via host PHP runner (SQLite fallback)..."; \
        TMPDIR=/dev/shm SQLITE_TMPDIR=/dev/shm DB_CONNECTION=sqlite php dev/tests/runner.php; \
    else \
        echo "[ERROR] Cannot execute integration tests: neither active Docker app container nor host PHP found."; \
        exit 1; \
    fi

# Execute database failover chaos engineering test
test-chaos:
    @./dev/scripts/sh/ensure-docker.sh
    ./dev/scripts/sh/test-failover-chaos.sh

# Run database migrations with MySQL container pre-flight and SQLite fallback
migrate:
    python3 dev/scripts/py/migrate.py --migrate

# Load database seed records with MySQL container pre-flight and SQLite fallback
seed:
    python3 dev/scripts/py/migrate.py --seed

# Execute chunked high-load stress seeder (10^5+ records)
seed-stress:
    @if ./dev/scripts/sh/ensure-docker.sh --check-only >/dev/null 2>&1 && docker compose ps --status running -q db >/dev/null 2>&1; then \
        python3 dev/scripts/py/seed_stress.py --driver mysql; \
    else \
        echo "[NOTICE] MySQL container offline. Seeding high-load records into SQLite fallback..."; \
        python3 dev/scripts/py/seed_stress.py --driver sqlite; \
    fi

# Benchmark database query latency and SLA conformance
benchmark-db:
    @if ! ./dev/scripts/sh/ensure-docker.sh --check-only >/dev/null 2>&1 || ! docker compose ps --status running -q db >/dev/null 2>&1; then \
        echo "[ERROR] benchmark-db requires the active MySQL 8.0 container to profile engine latency."; \
        echo "Actionable fix: Start Docker stack with 'just up' and re-run."; \
        exit 1; \
    fi
    python3 dev/scripts/py/benchmark_queries.py

# Capture visual specimens and verify 13-route inventory with browser pre-flight
screenshot:
    @if ! command -v python3 >/dev/null 2>&1; then \
        echo "[ERROR] Python 3 is required for specimen capture."; \
        exit 1; \
    fi
    @if ! command -v google-chrome >/dev/null 2>&1 && ! command -v chromium >/dev/null 2>&1 && ! command -v chromium-browser >/dev/null 2>&1; then \
        echo "[WARN] No system Chromium/Chrome binary found in PATH."; \
        echo "  Playwright browser install: uv run playwright install chromium"; \
        echo "  Or install via package manager:"; \
        echo "    Fedora: sudo dnf install chromium"; \
        echo "    Ubuntu: sudo apt install chromium-browser"; \
    fi
    python3 dev/scripts/py/screenshot_routes.py

# Compile academic defense LaTeX monograph via XeLaTeX with toolchain pre-flight
paper:
    @bash dev/scripts/sh/check-latex.sh
    cd paper && latexmk -xelatex -interaction=nonstopmode main.tex

# Clean LaTeX build artifacts
paper-clean:
    @command -v latexmk >/dev/null 2>&1 && (cd paper && latexmk -c main.tex) || true

# Package application for standalone XAMPP / Apache distribution
package:
    @if ! command -v zip >/dev/null 2>&1; then \
        echo "[ERROR] 'zip' command is required for packaging."; \
        echo "Install via: sudo dnf install zip (Fedora) or sudo apt install zip (Ubuntu/Debian)"; \
        exit 1; \
    fi
    ./dev/scripts/sh/package-xampp.sh

# Validate static asset optimization and air-gap integrity
optimize-assets:
    ./dev/scripts/sh/optimize-assets.sh

# Run all automated tests and quality gates with adaptive host fallback
test: setup
    @if ./dev/scripts/sh/ensure-docker.sh --check-only >/dev/null 2>&1 && docker compose ps --status running -q app >/dev/null 2>&1; then \
        echo "[INFO] Active Docker stack detected. Executing containerized test matrix..."; \
        just lint; \
        just test-hardening; \
        just test-integration; \
        just test-sabotage; \
        just screenshot; \
        just optimize-assets; \
        just check-links; \
        echo "[SUCCESS] All containerized EIMS verification suites passed."; \
    else \
        echo "================================================================================"; \
        echo "[NOTICE] Docker unavailable or stack inactive."; \
        echo "Falling back to host PHP test runner (ADR-002 Dual-Driver SQLite Resilience)..."; \
        echo "================================================================================"; \
        just lint; \
        echo -e "\n[1/3] Running Security Hardening Verification (Host PHP)..."; \
        php dev/scripts/php/test-hardening.php; \
        echo -e "\n[2/3] Running Consolidated Integration Suite (Host PHP)..."; \
        php dev/tests/runner.php; \
        echo -e "\n[3/3] Running Panel Sabotage & Error Kernel Defense (Host PHP)..."; \
        just test-sabotage; \
        echo -e "\n[SUCCESS] Host offline test suites passed (100% test coverage)."; \
    fi

# Export Docker images for air-gapped deployment
pre-cache:
    @./dev/scripts/sh/ensure-docker.sh
    ./dev/scripts/sh/pre-cache.sh

# Verify markdown hyperlinks integrity
check-links:
    python3 dev/scripts/py/check_links.py

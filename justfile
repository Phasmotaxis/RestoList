# EIMS Operational Justfile
# Non-interactive task runner for Docker lifecycle and quality gates

set shell := ["bash", "-c"]

# Default recipe: list available recipes
default:
    @just --list

# Initialize environment and directories
setup:
    @test -f .env || cp .env.example .env
    @mkdir -p storage dev/tmp docs/specimens/screenshots
    @chmod 777 storage dev/tmp docs/specimens/screenshots 2>/dev/null || true
    @echo "[SUCCESS] Environment configured (.env, storage/, dev/tmp/, docs/specimens/screenshots/)"

# Build container images
build: setup
    docker compose build

# Start services in detached mode
up: setup
    docker compose up -d

# Stop running services
down:
    docker compose down

# Follow service logs
logs:
    docker compose logs -f

# Execute PHP syntax lint across application source
lint:
    docker compose exec app find /var/www/html -name "*.php" -exec php -l {} +

# Execute panel sabotage defense verification
test-sabotage:
    ./dev/scripts/sh/test-panel-sabotage.sh

# Execute application security, injection, and hardening verification suite
test-hardening:
    docker run --rm -v "$PWD/src":/app -v "$PWD/dev":/dev-scripts:ro -e DB_CONNECTION=sqlite -e DB_SQLITE_PATH=/tmp/hardening.sqlite -w /app php:8.3-cli php /dev-scripts/scripts/php/test-hardening.php

# Execute consolidated integration test suite (RBAC, logout, rate-limit, XSS, CSP, debug suppression, TableAllocator)
test-integration:
    docker run --rm -v "$PWD/tests":/tests:ro -v "$PWD/src":/src:ro -e DB_CONNECTION=sqlite -e DB_SQLITE_PATH=/tmp/test.sqlite -w /tests php:8.3-cli php runner.php

# Execute database failover chaos engineering test
test-chaos:
    ./dev/scripts/sh/test-failover-chaos.sh

# Run database migrations
migrate:
    python3 dev/scripts/py/migrate.py --migrate

# Load database seed records
seed:
    python3 dev/scripts/py/migrate.py --seed

# Execute chunked high-load stress seeder (10^5+ records)
seed-stress:
    python3 dev/scripts/py/seed_stress.py

# Benchmark database query latency and SLA conformance
benchmark-db:
    python3 dev/scripts/py/benchmark_queries.py

# Capture visual specimens and verify 13-route inventory
screenshot:
    python3 dev/scripts/py/screenshot_routes.py

# Compile academic defense LaTeX monograph via XeLaTeX
paper:
    cd Paper && latexmk -xelatex -interaction=nonstopmode main.tex

# Clean LaTeX build artifacts
paper-clean:
    cd Paper && latexmk -c main.tex

# Validate static asset optimization and air-gap integrity
optimize-assets:
    ./dev/scripts/sh/optimize-assets.sh

# Run all automated tests and quality gates
test: setup lint test-hardening test-integration test-sabotage screenshot optimize-assets check-links
    @echo "[SUCCESS] All EIMS verification suites passed."

# Export Docker images for air-gapped deployment
pre-cache:
    ./dev/scripts/sh/pre-cache.sh

# Verify markdown hyperlinks integrity
check-links:
    python3 dev/scripts/py/check_links.py

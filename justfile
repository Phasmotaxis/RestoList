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

# Run database migrations
migrate:
    python3 dev/scripts/py/migrate.py --migrate

# Load database seed records
seed:
    python3 dev/scripts/py/migrate.py --seed

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
test: setup lint test-sabotage screenshot optimize-assets
    @echo "[SUCCESS] All EIMS verification suites passed."

# Export Docker images for air-gapped deployment
pre-cache:
    ./dev/scripts/sh/pre-cache.sh

# Verify markdown hyperlinks integrity
check-links:
    @python3 -c "import os, re, sys; errors = []; [errors.extend([f'{root}/{f} -> {m}' for m in re.findall(r'\[.*?\]\((?!http|#)(.*?)\)', open(os.path.join(root, f)).read()) if not os.path.exists(os.path.normpath(os.path.join(root, m.split('#')[0])))]) for root, _, files in os.walk('.') if 'condom' not in root and '.git' not in root for f in files if f.endswith('.md')]; sys.exit(f'Broken links:\n' + '\n'.join(errors) if errors else print('[SUCCESS] All markdown links verified.'))"

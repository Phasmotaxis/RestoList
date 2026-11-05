# EIMS Operational Justfile
# Non-interactive task runner for Docker lifecycle and quality gates

set shell := ["bash", "-c"]

# Default recipe: list available recipes
default:
    @just --list

# Initialize environment and directories
setup:
    @test -f .env || cp .env.example .env
    @mkdir -p storage dev/tmp
    @echo "[SUCCESS] Environment configured (.env, storage/, dev/tmp/)"

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

# Run tests and configuration verification
test: setup
    @echo "[INFO] Validating Docker Compose configuration..."
    docker compose config --quiet
    @echo "[INFO] Checking required directory structure..."
    @test -f README.md && test -f docs/CHANGELOG.md && test -f docs/GETTING-STARTED.md
    @echo "[INFO] Running panel sabotage and kernel tests..."
    ./dev/scripts/sh/test-panel-sabotage.sh
    @echo "[SUCCESS] Base verification suite passed."

# Export Docker images for air-gapped deployment
pre-cache:
    ./dev/scripts/sh/pre-cache.sh

# Verify markdown hyperlinks integrity
check-links:
    @python3 -c "import os, re, sys; errors = []; [errors.extend([f'{root}/{f} -> {m}' for m in re.findall(r'\[.*?\]\((?!http|#)(.*?)\)', open(os.path.join(root, f)).read()) if not os.path.exists(os.path.normpath(os.path.join(root, m.split('#')[0])))]) for root, _, files in os.walk('.') if 'condom' not in root and '.git' not in root for f in files if f.endswith('.md')]; sys.exit(f'Broken links:\n' + '\n'.join(errors) if errors else print('[SUCCESS] All markdown links verified.'))"

#!/usr/bin/env bash
# ==============================================================================
# DICT24 Restaurant ERP: XAMPP Distribution Packaging Script
# Packages application source, SQL bootstrap schema, and runtime config
# into a production-ready, air-gapped zip file for Apache/MySQL (XAMPP).
# ==============================================================================

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
DIST_DIR="${REPO_ROOT}/dist"
STAGE_DIR="${REPO_ROOT}/dev/tmp/xampp-build"
ZIP_TARGET="${DIST_DIR}/dict24-resto-erp-xampp.zip"
SQL_SOURCE="${REPO_ROOT}/database/dict24_resto_erp.sql"

echo "[INFO] === Starting DICT24 Restaurant ERP XAMPP Packaging ==="

# 1. Ensure target directories
mkdir -p "${DIST_DIR}" "${STAGE_DIR}"
rm -rf "${STAGE_DIR:?}"/*

# 2. Verify SQL schema exists
if [[ ! -f "${SQL_SOURCE}" ]]; then
    echo "[WARN] SQL schema file ${SQL_SOURCE} not found; generating fallback from migrations..."
    mkdir -p "$(dirname "${SQL_SOURCE}")"
    cat "${REPO_ROOT}"/src/database/migrations/*.sql > "${SQL_SOURCE}"
    cat "${REPO_ROOT}"/src/database/seeds/*.sql >> "${SQL_SOURCE}"
fi

# 3. Copy application files to staging directory
echo "[INFO] Staging application source and assets..."
mkdir -p "${STAGE_DIR}/app" "${STAGE_DIR}/public" "${STAGE_DIR}/storage" "${STAGE_DIR}/database"

cp -R "${REPO_ROOT}/src/app/"* "${STAGE_DIR}/app/"
cp -R "${REPO_ROOT}/src/public/"* "${STAGE_DIR}/public/"
cp "${SQL_SOURCE}" "${STAGE_DIR}/database/dict24_resto_erp.sql"

# 4. Generate XAMPP-ready .env configuration
cat << 'EOF' > "${STAGE_DIR}/.env"
# DICT24 Restaurant ERP - XAMPP Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost/dict24-resto-erp/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=eims_db
DB_USER=root
DB_PASS=
DB_SQLITE_PATH=storage/fallback.sqlite
EOF

cp "${STAGE_DIR}/.env" "${STAGE_DIR}/.env.example"

# 5. Create XAMPP Setup Runbook
cat << 'EOF' > "${STAGE_DIR}/README-XAMPP.txt"
================================================================================
DICT24 Multi-Tenant Restaurant ERP & Reservation Platform - XAMPP Setup
================================================================================

PREREQUISITES:
1. XAMPP with PHP 8.2 or 8.3 and MySQL/MariaDB running.
2. Apache mod_rewrite enabled (default in XAMPP).

INSTALLATION INSTRUCTIONS:
1. Extract this zip archive into your XAMPP htdocs folder:
   - Windows: C:\xampp\htdocs\dict24-resto-erp
   - Linux:   /opt/lampp/htdocs/dict24-resto-erp

2. Open phpMyAdmin (http://localhost/phpmyadmin/):
   - Create a new database named: eims_db
   - Collation: utf8mb4_unicode_ci
   - Import the SQL file: database/dict24_resto_erp.sql

3. Review configuration in .env:
   - DB_HOST=127.0.0.1
   - DB_PORT=3306
   - DB_NAME=eims_db
   - DB_USER=root
   - DB_PASS= (leave empty if default XAMPP root without password)

4. Access the application in your browser:
   - http://localhost/dict24-resto-erp/public/

DEFAULT DEMO CREDENTIALS:
- SuperAdmin: superadmin@cvsu.edu.ph / Password123!
- Admin:      admin@cvsu.edu.ph      / Password123!
- Staff:      user@cvsu.edu.ph       / Password123!
- FrontOfHouse: foh@cvsu.edu.ph      / Password123!
- Kitchen:    kitchen@cvsu.edu.ph    / Password123!
================================================================================
EOF

# 6. Build zip archive
echo "[INFO] Creating zip archive at ${ZIP_TARGET}..."
rm -f "${ZIP_TARGET}"
(cd "${STAGE_DIR}" && zip -qr "${ZIP_TARGET}" .)

# 7. Verify zip package
if [[ -f "${ZIP_TARGET}" ]]; then
    ZIP_SIZE=$(ls -lh "${ZIP_TARGET}" | awk '{print $5}')
    echo "[SUCCESS] Packaged ${ZIP_TARGET} (${ZIP_SIZE})"
    unzip -t "${ZIP_TARGET}" > /dev/null
    echo "[SUCCESS] Archive integrity verified (exit 0)."
else
    echo "[ERROR] Failed to create ${ZIP_TARGET}" >&2
    exit 1
fi

# 8. Clean up staging directory
rm -rf "${STAGE_DIR}"
echo "[SUCCESS] Packaging complete."

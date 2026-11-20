#!/usr/bin/env bash
# EIMS Chaos Engineering & Dynamic Failover Test Suite
# Asserts high resilience, zero-crash degradation, and SQLite fallback on MySQL container kill

set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"

echo "================================================================================"
echo " [CHAOS TEST] Initiating Database Failover & Container Resilience Verification"
echo "================================================================================"

# 1. Ensure stack is running
echo "[PHASE 1] Booting and verifying primary container stack..."
docker compose up -d

# Wait for health probe to report healthy MySQL
MAX_WAIT=30
WAITED=0
while [ $WAITED -lt $MAX_WAIT ]; do
    HEALTH_JSON=$(curl -s "${BASE_URL}/health" || true)
    if echo "${HEALTH_JSON}" | grep -Eq '"status"[[:space:]]*:[[:space:]]*"healthy"' && echo "${HEALTH_JSON}" | grep -Eq '"driver"[[:space:]]*:[[:space:]]*"mysql"'; then
        echo "[INFO] Stack is healthy. Primary driver: MySQL 8.0"
        break
    fi
    sleep 1
    WAITED=$((WAITED + 1))
done

if [ $WAITED -ge $MAX_WAIT ]; then
    echo "[FAIL] Timed out waiting for primary MySQL stack to become healthy."
    exit 1
fi

# 2. Forcibly kill database container (simulating sudden crash or power disruption)
echo "[PHASE 2] Forcibly killing MySQL database container (docker compose kill db)..."
docker compose kill db

sleep 2

# 3. Dispatch HTTP GET request to /health and verify resilient fallback
echo "[PHASE 3] Dispatching probe to /health under database outage conditions..."
HEALTH_DEGRADED=$(curl -s -w "\n%{http_code}" "${BASE_URL}/health")
HTTP_CODE=$(echo "${HEALTH_DEGRADED}" | tail -n 1)
BODY=$(echo "${HEALTH_DEGRADED}" | head -n -1)

echo "[RESPONSE] HTTP Status: ${HTTP_CODE}"
echo "[RESPONSE] Payload: ${BODY}"

if [ "${HTTP_CODE}" != "200" ]; then
    echo "[FAIL] Expected HTTP 200 under failover, received: ${HTTP_CODE}"
    docker compose up -d db
    exit 1
fi

if ! echo "${BODY}" | grep -Eq '"status"[[:space:]]*:[[:space:]]*"degraded"'; then
    echo "[FAIL] Expected 'status': 'degraded' in response."
    docker compose up -d db
    exit 1
fi

if ! echo "${BODY}" | grep -Eq '"driver"[[:space:]]*:[[:space:]]*"sqlite"'; then
    echo "[FAIL] Expected 'driver': 'sqlite' in response."
    docker compose up -d db
    exit 1
fi

if ! echo "${BODY}" | grep -Eq '"resilient"[[:space:]]*:[[:space:]]*true'; then
    echo "[FAIL] Expected 'resilient': true in response."
    docker compose up -d db
    exit 1
fi

echo "[ASSERT 1 PASSED] /health returned HTTP 200 with degraded SQLite resilience."

# 4. Verify /login route remains accessible under fallback
echo "[PHASE 4] Verifying /login route accessibility under SQLite fallback..."
LOGIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/login")
if [ "${LOGIN_STATUS}" != "200" ]; then
    echo "[FAIL] Expected /login HTTP 200, received: ${LOGIN_STATUS}"
    docker compose up -d db
    exit 1
fi
echo "[ASSERT 2 PASSED] /login returned HTTP 200 without white screen disruption."

# 5. Verify write persistence in SQLite while MySQL remains dead
echo "[PHASE 5] Verifying write persistence in SQLite storage..."
CHAOS_TENANT_ID="chaos-tenant-$(date +%s%N)"
CHAOS_SUBDOMAIN="chaos-$(date +%s%N)"
docker compose exec -T app php -r "
require_once '/var/www/html/app/Core/Database.php';
\App\Core\Database::executeQuery(
    'INSERT INTO tenants (id, name, subdomain, is_active) VALUES (:id, :name, :sub, 1)',
    ['id' => '${CHAOS_TENANT_ID}', 'name' => 'Chaos Resilient Org', 'sub' => '${CHAOS_SUBDOMAIN}']
);
echo '[PHP] Successfully persisted record to SQLite fallback.';
"

# Assert tenant exists in SQLite
COUNT=$(docker compose exec -T app php -r "
require_once '/var/www/html/app/Core/Database.php';
\$stmt = \App\Core\Database::executeQuery('SELECT COUNT(*) as c FROM tenants WHERE id = :id', ['id' => '${CHAOS_TENANT_ID}']);
echo \$stmt->fetch()['c'];
")

if [ "${COUNT}" != "1" ]; then
    echo "[FAIL] Expected 1 tenant written to SQLite, found: ${COUNT}"
    docker compose up -d db
    exit 1
fi
echo "[ASSERT 3 PASSED] Write persistence verified in SQLite fallback."

# 6. Restart MySQL container and verify stack recovery
echo "[PHASE 6] Restarting MySQL database container (docker compose up -d db)..."
docker compose up -d db

echo "[INFO] Waiting for MySQL container to become ready..."
RECOVER_MAX=30
RECOVER_WAITED=0
while [ $RECOVER_WAITED -lt $RECOVER_MAX ]; do
    if docker compose exec -T db mysqladmin ping -u eims_user -peims_password --silent >/dev/null 2>&1; then
        echo "[INFO] MySQL container restored and responding."
        break
    fi
    sleep 1
    RECOVER_WAITED=$((RECOVER_WAITED + 1))
done

if [ $RECOVER_WAITED -ge $RECOVER_MAX ]; then
    echo "[FAIL] Timed out waiting for MySQL container to recover."
    exit 1
fi

# Reset app database connection to return to MySQL
docker compose exec -T app php -r "
require_once '/var/www/html/app/Core/Database.php';
\App\Core\Database::resetConnection();
"

# Check health returns healthy MySQL
HEALTH_RESTORED=$(curl -s "${BASE_URL}/health")
echo "[RESPONSE] Restored Payload: ${HEALTH_RESTORED}"

if echo "${HEALTH_RESTORED}" | grep -Eq '"status"[[:space:]]*:[[:space:]]*"healthy"' && echo "${HEALTH_RESTORED}" | grep -Eq '"driver"[[:space:]]*:[[:space:]]*"mysql"'; then
    echo "[ASSERT 4 PASSED] Primary MySQL driver restored and telemetry healthy."
else
    echo "[FAIL] Healthcheck did not restore to healthy MySQL driver."
    exit 1
fi

echo "================================================================================"
echo " [SUCCESS] Container Chaos Failover Test Suite Passed Empirically."
echo "================================================================================"

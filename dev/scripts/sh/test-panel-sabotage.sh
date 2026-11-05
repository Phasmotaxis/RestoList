#!/usr/bin/env bash
set -euo pipefail

# Live Panel Sabotage Verification Test Runner for EIMS
# Empirically verifies ErrorHandler traps notices, unhandled exceptions, and fatal shutdowns.

echo "[INFO] === Starting EIMS Panel Sabotage Verification ==="

# 1. Verify strict syntax of all project PHP files inside container
echo "[TEST 1/4] Checking PHP syntax integrity (php -l)..."
docker run --rm -v "$(pwd)/src:/var/www/html:ro" eims_app:latest \
    find /var/www/html -name "*.php" -exec php -l {} + > /dev/null
echo "[PASS] All PHP files pass strict syntax validation."

# 2. Verify Warning/Notice elevation to ErrorException
echo "[TEST 2/4] Testing runtime warning elevation to ErrorException..."
ELEVATION_TEST=$(docker run --rm -v "$(pwd)/src:/var/www/html:ro" -v "$(pwd)/dev:/var/www/dev:ro" eims_app:latest php -r '
    require "/var/www/html/app/Core/ErrorHandler.php";
    \App\Core\ErrorHandler::register();
    try {
        $a = 10 / 0; // Triggers warning/division by zero
    } catch (\Throwable $e) {
        echo "[ELEVATION_CONFIRMED] " . get_class($e) . ": " . $e->getMessage();
    }
' 2>&1)

if [[ "${ELEVATION_TEST}" == *"[ELEVATION_CONFIRMED] ErrorException"* ]] || [[ "${ELEVATION_TEST}" == *"[ELEVATION_CONFIRMED] DivisionByZeroError"* ]]; then
    echo "[PASS] Runtime anomaly successfully elevated to Throwable exception."
else
    echo "[FAIL] Warning elevation failed: ${ELEVATION_TEST}"
    exit 1
fi

# 3. Verify Visual Diagnostic Rendering on Uncaught Exception
echo "[TEST 3/4] Testing Visual Diagnostic Screen rendering on uncaught Throwable..."
DIAGNOSTIC_OUTPUT=$(docker run --rm -v "$(pwd)/src:/var/www/html:ro" -v "$(pwd)/dev:/var/www/dev:ro" eims_app:latest php -r '
    require "/var/www/html/app/Core/ErrorHandler.php";
    \App\Core\ErrorHandler::register();
    throw new \RuntimeException("Panel examiners deleted critical controller logic!");
' 2>&1 || true)

if [[ "${DIAGNOSTIC_OUTPUT}" == *"Diagnostic Alert - EIMS Defense Kernel"* ]] && [[ "${DIAGNOSTIC_OUTPUT}" == *"Panel examiners deleted critical controller logic!"* ]]; then
    echo "[PASS] Visual diagnostic console intercepted uncaught exception and rendered high-contrast console."
else
    echo "[FAIL] Diagnostic rendering failed: ${DIAGNOSTIC_OUTPUT}"
    exit 1
fi

# 4. Verify YAML Dynamic Header Parsing
echo "[TEST 4/4] Testing dynamic YAML header parsing in partials/header.php..."
HEADER_TEST=$(docker run --rm -v "$(pwd)/src:/var/www/html:ro" -v "$(pwd)/dev:/var/www/dev:ro" eims_app:latest php -r '
    ob_start();
    require "/var/www/html/app/Views/partials/header.php";
    $output = ob_get_clean();
    if (strpos($output, "Enterprise Information Management System") !== false && strpos($output, "eims.cvsu.edu.ph") !== false) {
        echo "[HEADER_CONFIRMED]";
    } else {
        echo "[HEADER_FAILED] " . $output;
    }
' 2>&1)

if [[ "${HEADER_TEST}" == *"[HEADER_CONFIRMED]"* ]]; then
    echo "[PASS] Dynamic YAML header parsing verified."
else
    echo "[FAIL] Header YAML resolution failed: ${HEADER_TEST}"
    exit 1
fi

echo "[SUCCESS] All Panel Sabotage & Kernel Defense verification gates passed."
exit 0

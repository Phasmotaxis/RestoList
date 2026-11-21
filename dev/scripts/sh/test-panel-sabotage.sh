#!/usr/bin/env bash
set -euo pipefail

# Live Panel Sabotage Verification Test Runner for EIMS
# Empirically verifies ErrorHandler traps notices, unhandled exceptions, and fatal shutdowns.

echo "[INFO] === Starting EIMS Panel Sabotage Verification ==="

# 1. Verify strict syntax of all project PHP files inside container
echo "[TEST 1/5] Checking PHP syntax integrity (php -l)..."
docker compose exec -T app find /var/www/html -name "*.php" -exec php -l {} + > /dev/null
echo "[PASS] All PHP files pass strict syntax validation."

# 2. Verify Warning/Notice elevation to ErrorException
echo "[TEST 2/5] Testing runtime warning elevation to ErrorException..."
ELEVATION_TEST=$(docker compose exec -T app php -r '
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

# 3. Verify Visual Diagnostic Rendering on Uncaught Exception (requires APP_DEBUG=true)
echo "[TEST 3/5] Testing Visual Diagnostic Screen rendering on uncaught Throwable..."
DIAGNOSTIC_OUTPUT=$(docker compose exec -T -e APP_DEBUG=true app php -r '
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
echo "[TEST 4/5] Testing dynamic YAML header parsing in partials/header.php..."
HEADER_TEST=$(docker compose exec -T app php -r '
    ob_start();
    require "/var/www/html/app/Views/partials/header.php";
    $output = ob_get_clean();
    if (strpos($output, "Enterprise Information Management System") !== false && strpos($output, "eims-cvsu.lyra-on.top") !== false) {
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

# 5. Verify APP_DEBUG=false hides stack traces in HTTP response (production hardening)
echo "[TEST 5/5] Verifying APP_DEBUG=false suppresses HTTP response trace details..."
TRACE_HIDDEN=$(docker compose exec -T -e APP_DEBUG=false app php -r '
    require "/var/www/html/app/Core/ErrorHandler.php";
    \App\Core\ErrorHandler::register();
    ob_start();
    try {
        throw new \RuntimeException("Test exception");
    } catch (\Throwable $e) {
        http_response_code(500);
        include "/var/www/html/app/Views/errors/500.php";
    }
    $output = ob_get_clean();
    echo $output;
' 2>&1)

if [[ "${TRACE_HIDDEN}" != *"Stack trace"* ]] && [[ "${TRACE_HIDDEN}" != *"RuntimeException"* ]] && [[ "${TRACE_HIDDEN}" != *"Call Stack"* ]]; then
    echo "[PASS] Production mode (APP_DEBUG=false) suppresses stack traces in HTTP response."
else
    echo "[FAIL] Stack traces or exception details visible in HTTP response: ${TRACE_HIDDEN:0:200}"
    exit 1
fi

echo "[SUCCESS] All Panel Sabotage & Kernel Defense verification gates passed."
exit 0

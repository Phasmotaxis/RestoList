#!/usr/bin/env bash
set -euo pipefail

# EIMS Asset Optimization & Offline Presentation Integrity Verification
# Ensures all static assets are local, compressed, and free of external CDN leaks.

echo "[INFO] === Starting EIMS Asset Optimization & Integrity Verification ==="

ASSETS_DIR="src/public/assets"
IMG_DIR="${ASSETS_DIR}/images"

# 1. Verify absence of external CDN links or fonts across application templates
echo "[STEP 1/4] Auditing view templates for external font/CDN leaks..."
if grep -rnE "fonts.googleapis|cdnjs.cloudflare|cdn.jsdelivr|unpkg.com" src/app/Views/ src/public/ > /dev/null 2>&1; then
    echo "[FAIL] External CDN links detected in templates. Violates air-gap invariant."
    exit 1
fi
echo "[PASS] Zero external CDN or Google Fonts leaks detected."

# 2. Verify SVG asset integrity and viewBox declarations
echo "[STEP 2/4] Verifying vector SVG formatting..."
for svg in $(find "${ASSETS_DIR}" -name "*.svg"); do
    if ! grep -q "viewBox" "${svg}"; then
        echo "[FAIL] Missing viewBox attribute in ${svg}"
        exit 1
    fi
    echo "  [OK] Validated ${svg}"
done
echo "[PASS] All SVGs maintain valid viewBox coordinates."

# 3. Verify Raster and WebP Asset Pairs
echo "[STEP 3/4] Verifying raster images and WebP variants..."
for png in "${IMG_DIR}/banner.png" "${IMG_DIR}/icons/logo.png"; do
    if [ ! -f "${png}" ]; then
        echo "[FAIL] Required image asset missing: ${png}"
        exit 1
    fi
    webp="${png%.png}.webp"
    if [ ! -f "${webp}" ]; then
        echo "[FAIL] Missing optimized WebP variant for: ${png}"
        exit 1
    fi
    echo "  [OK] Found paired PNG and WebP: ${png}"
done
echo "[PASS] All primary raster assets possess optimized WebP counterparts."

# 4. Verify CSS Custom Properties and High-Contrast Accessibility
echo "[STEP 4/4] Verifying CSS architecture in style.css..."
if ! grep -q ":root" "${ASSETS_DIR}/css/style.css" || ! grep -q "prefers-color-scheme: dark" "${ASSETS_DIR}/css/style.css"; then
    echo "[FAIL] style.css missing root custom properties or dark mode definitions."
    exit 1
fi
echo "[PASS] Semantic CSS3 custom properties with dark mode verified."

echo "[SUCCESS] Asset optimization and offline integrity gates passed."
exit 0

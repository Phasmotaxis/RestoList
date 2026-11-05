#!/usr/bin/env bash
set -euo pipefail

# Offline Docker Image Pre-Cache Bundler for EIMS
# Ensures container images can be saved and loaded in air-gapped defense environments.

OUTPUT_DIR="dev/tmp"
ARCHIVE_PATH="${OUTPUT_DIR}/offline-images.tar"
REQUIRED_IMAGES=(
    "nginx:1.25-alpine"
    "php:8.3-fpm-alpine"
    "mysql:8.0"
)

mkdir -p "${OUTPUT_DIR}"

echo "[INFO] Verifying and pulling required base images..."
for img in "${REQUIRED_IMAGES[@]}"; do
    echo "[INFO] Pulling ${img}..."
    docker pull "${img}"
done

echo "[INFO] Exporting images into ${ARCHIVE_PATH}..."
docker save -o "${ARCHIVE_PATH}" "${REQUIRED_IMAGES[@]}"

if [[ -f "${ARCHIVE_PATH}" ]]; then
    ARCHIVE_SIZE=$(stat -c%s "${ARCHIVE_PATH}" 2>/dev/null || stat -f%z "${ARCHIVE_PATH}")
    if [[ "${ARCHIVE_SIZE}" -gt 1048576 ]]; then
        echo "[SUCCESS] Offline image bundle created: ${ARCHIVE_PATH} (${ARCHIVE_SIZE} bytes)"
        exit 0
    else
        echo "[ERROR] Archive created but suspiciously small (${ARCHIVE_SIZE} bytes)."
        exit 1
    fi
else
    echo "[ERROR] Failed to generate ${ARCHIVE_PATH}."
    exit 1
fi

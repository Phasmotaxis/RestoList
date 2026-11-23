#!/usr/bin/env bash
# ==============================================================================
# EIMS Docker Daemon Auto-Escalation & Diagnostics Guard
# Verifies Docker connectivity across Linux, macOS, and Windows/WSL2.
# Attempts non-interactive auto-start and provides actionable recovery guidance.
# ==============================================================================
set -euo pipefail

# ANSI Color Codes
BOLD='\033[1m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

QUIET=false
CHECK_ONLY=false
FORCE_FIX=false
CHECK_PORTS=false
TARGET_PORTS=(8080 3306)

for arg in "$@"; do
    case "$arg" in
        -q|--quiet) QUIET=true ;;
        --check-only) CHECK_ONLY=true ;;
        --fix) FORCE_FIX=true ;;
        --check-ports) CHECK_PORTS=true ;;
        --ports=*)
            IFS=',' read -r -a TARGET_PORTS <<< "${arg#*=}"
            CHECK_PORTS=true
            ;;
    esac
done

check_port_collision() {
    local ports=("$@")
    local collision_found=0

    for port in "${ports[@]}"; do
        local proc_info=""
        if command -v lsof >/dev/null 2>&1; then
            proc_info=$(lsof -iTCP:"$port" -sTCP:LISTEN -n -P 2>/dev/null | awk 'NR>1 {print $1, $2}' | head -n 1 || true)
        fi
        if [ -z "$proc_info" ] && command -v ss >/dev/null 2>&1; then
            local ss_out
            ss_out=$(ss -tlpn "sport = :$port" 2>/dev/null | grep -E "users:\(\(" | head -n 1 || true)
            if [ -n "$ss_out" ]; then
                proc_info=$(echo "$ss_out" | sed -E 's/.*users:\(\("([^"]+)",pid=([0-9]+).*/\1 \2/')
            elif ss -tlpn "sport = :$port" 2>/dev/null | grep -q "LISTEN"; then
                proc_info="system-service [privileged]"
            fi
        fi
        if [ -z "$proc_info" ] && command -v fuser >/dev/null 2>&1; then
            local fuser_out
            fuser_out=$(fuser "$port/tcp" 2>/dev/null || true)
            if [ -n "$fuser_out" ]; then
                proc_info="process $fuser_out"
            fi
        fi

        if [ -n "$proc_info" ]; then
            local proc_name
            proc_name=$(echo "$proc_info" | awk '{print $1}')
            local pid
            pid=$(echo "$proc_info" | awk '{print $2}')

            if [[ "$proc_name" =~ docker|dockerd|docker-proxy|containerd ]]; then
                if [[ "$QUIET" == false ]]; then
                    echo -e "${CYAN}[INFO]${NC} Port $port is managed by Docker ($proc_name PID ${pid:-N/A})."
                fi
            else
                echo -e "${RED}[ERROR]${NC} Port collision detected! Port $port is already bound by '$proc_name' (PID ${pid:-unknown})." >&2
                echo "  Actionable fix:" >&2
                if [ -n "$pid" ] && [ "$pid" != "unknown" ] && [ "$pid" != "[privileged]" ]; then
                    echo "    Stop conflicting process: sudo kill -9 $pid" >&2
                fi
                echo "    Stop conflicting service: sudo systemctl stop $proc_name (or brew services stop $proc_name)" >&2
                echo "    Or change port in .env:   APP_PORT=<free_port> or DB_PORT=<free_port>" >&2
                collision_found=1
            fi
        fi
    done

    if [ "$collision_found" -ne 0 ]; then
        return 1
    fi
    return 0
}

if [[ "$CHECK_PORTS" == true ]]; then
    if ! check_port_collision "${TARGET_PORTS[@]}"; then
        echo -e "${RED}[ERROR]${NC} Aborting container startup due to port collision." >&2
        exit 1
    fi
fi

# Step 1: Fast path: test docker info directly. If active, print success and exit 0.
if [[ "$FORCE_FIX" == false ]] && docker info >/dev/null 2>&1; then
    if [[ "$QUIET" == false ]]; then
        echo -e "${GREEN}[SUCCESS] Docker daemon is ready.${NC}"
    fi
    exit 0
fi

if [[ "$CHECK_ONLY" == true ]]; then
    exit 1
fi

# Step 2: Detect Operating System & Runtime Environment
detect_os() {
    local uname_s
    uname_s="$(uname -s 2>/dev/null || echo 'Unknown')"
    if [[ "$uname_s" == "Darwin" ]] || [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    elif grep -qi "microsoft" /proc/version 2>/dev/null || grep -qi "wsl" /proc/version 2>/dev/null || [[ -n "${WSL_DISTRO_NAME:-}" ]]; then
        echo "wsl"
    elif [[ "$uname_s" == "Linux" ]] || [[ "$OSTYPE" == "linux-gnu"* ]]; then
        echo "linux"
    elif [[ "$uname_s" =~ ^(MINGW|MSYS|CYGWIN) ]] || [[ "$OSTYPE" == "msys"* ]] || [[ "$OSTYPE" == "cygwin"* ]] || [[ "$OSTYPE" == "win32"* ]]; then
        echo "windows"
    else
        echo "unknown"
    fi
}

OS_TYPE="$(detect_os)"

if [[ "$QUIET" == false ]]; then
    echo -e "${YELLOW}[WARN]${NC} Docker daemon is not responding. Detected environment: ${BOLD}${OS_TYPE}${NC}."
    echo -e "${CYAN}[INFO]${NC} Initiating platform auto-escalation..."
fi

# Step 3: Platform-Specific Escalation Logic
case "$OS_TYPE" in
    linux|wsl)
        # Check systemctl docker status if systemctl is available
        if command -v systemctl >/dev/null 2>&1; then
            if ! systemctl is-active docker >/dev/null 2>&1 || [[ "$FORCE_FIX" == true ]]; then
                # Attempt passwordless sudo first, then fallback to interactive sudo if in TTY
                sudo -n systemctl start docker 2>/dev/null || ( [[ -t 0 ]] && sudo systemctl start docker 2>/dev/null ) || true
            fi
        elif command -v service >/dev/null 2>&1; then
            if sudo -n service docker status 2>/dev/null | grep -q "not running"; then
                sudo -n service docker start 2>/dev/null || ( [[ -t 0 ]] && sudo service docker start 2>/dev/null ) || true
            fi
        fi

        # If running under WSL, also attempt to launch Docker Desktop if available on Windows host
        if [[ "$OS_TYPE" == "wsl" ]] && command -v cmd.exe >/dev/null 2>&1; then
            cmd.exe /c "start \"\" \"C:\\Program Files\\Docker\\Docker\\Docker Desktop.exe\"" 2>/dev/null || true
        fi

        # Check user group & socket permissions
        DOCKER_SOCK="/var/run/docker.sock"
        if [[ -S "$DOCKER_SOCK" ]] && { ! [[ -r "$DOCKER_SOCK" ]] || ! [[ -w "$DOCKER_SOCK" ]]; }; then
            sudo -n chmod 666 "$DOCKER_SOCK" 2>/dev/null || ( [[ -t 0 ]] && sudo chmod 666 "$DOCKER_SOCK" 2>/dev/null ) || true
        fi
        ;;

    macos)
        TRIGGERED=false
        if command -v colima >/dev/null 2>&1 && colima status >/dev/null 2>&1; then
            echo -e "${CYAN}[INFO]${NC} Starting Colima daemon..."
            colima start 2>/dev/null || true
            TRIGGERED=true
        elif command -v orb >/dev/null 2>&1; then
            echo -e "${CYAN}[INFO]${NC} Launching OrbStack..."
            orb start 2>/dev/null || true
            TRIGGERED=true
        elif [[ -d "/Applications/Docker.app" ]] || command -v open >/dev/null 2>&1; then
            echo -e "${CYAN}[INFO]${NC} Launching Docker Desktop (/Applications/Docker.app)..."
            open -a Docker 2>/dev/null || open -g -a Docker 2>/dev/null || true
            TRIGGERED=true
        fi

        # If launch was triggered, loop with 20s timeout waiting for docker info
        if [[ "$TRIGGERED" == true ]]; then
            echo -e "${CYAN}[INFO]${NC} Waiting up to 20 seconds for Docker socket initialization..."
            for ((i=1; i<=20; i++)); do
                if docker info >/dev/null 2>&1; then
                    echo -e "${GREEN}[SUCCESS] Docker daemon is ready.${NC}"
                    exit 0
                fi
                sleep 1
            done
        fi
        ;;

    windows)
        # Windows environment: suggest launching Docker Desktop or invoke via PowerShell/cmd if available
        if command -v powershell.exe >/dev/null 2>&1; then
            powershell.exe -NoProfile -Command 'Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"' 2>/dev/null || true
        elif command -v cmd.exe >/dev/null 2>&1; then
            cmd.exe /c "start \"\" \"C:\\Program Files\\Docker\\Docker\\Docker Desktop.exe\"" 2>/dev/null || true
        fi
        ;;
esac

# Step 4: Re-check docker info
for ((i=1; i<=3; i++)); do
    if docker info >/dev/null 2>&1; then
        echo -e "${GREEN}[SUCCESS] Docker daemon is ready.${NC}"
        exit 0
    fi
    sleep 1
done

# Step 5: If still inactive, display clean, high-contrast banner with diagnostic advice and exit 1
DOCKER_SOCK="/var/run/docker.sock"
CURRENT_USER="${USER:-$(id -un 2>/dev/null || echo 'unknown')}"

echo ""
echo -e "${RED}================================================================================${NC}"
echo -e "${YELLOW}${BOLD}[DIAGNOSTIC] Docker daemon is not running.${NC}"
echo -e "Start it with:"
echo -e "  ${BOLD}sudo systemctl start docker${NC}"
echo -e "To run without sudo:"
echo -e "  ${BOLD}sudo usermod -aG docker ${CURRENT_USER} && newgrp docker${NC}"
echo -e "${RED}--------------------------------------------------------------------------------${NC}"
echo -e "${BOLD}Platform-Specific Remediation Guidance:${NC}"
case "$OS_TYPE" in
    macos)
        echo -e "  ${CYAN}[macOS]${NC}"
        echo -e "    1. Start Docker Desktop : ${BOLD}open -a Docker${NC}"
        echo -e "    2. Or start Colima      : ${BOLD}colima start${NC}"
        echo -e "    3. Or start OrbStack    : ${BOLD}orb start${NC}"
        ;;
    windows|wsl)
        echo -e "  ${CYAN}[Windows / WSL2]${NC}"
        echo -e "    1. Start Docker Desktop from the Windows Start Menu, or run in PowerShell:"
        echo -e "       ${BOLD}Start-Process \"C:\\Program Files\\Docker\\Docker\\Docker Desktop.exe\"${NC}"
        echo -e "    2. Ensure Docker Desktop -> Settings -> General -> 'Use the WSL 2 based engine' is enabled."
        echo -e "    3. Under Settings -> Resources -> WSL Integration, enable your Linux distribution."
        ;;
    linux|*)
        echo -e "  ${CYAN}[Linux]${NC}"
        echo -e "    1. Start daemon         : ${BOLD}sudo systemctl start docker${NC}"
        echo -e "    2. Fix socket ACL       : ${BOLD}sudo chmod 666 /var/run/docker.sock${NC}"
        echo -e "    3. Or run helper        : ${BOLD}just fix-docker${NC}"
        ;;
esac
echo ""
echo -e "${YELLOW}Note: EIMS supports offline testing! Run 'just test' for adaptive host PHP fallback.${NC}"
echo -e "${RED}================================================================================${NC}"
echo ""

exit 1

#!/usr/bin/env bash
# ==============================================================================
# EIMS System Doctor & Environmental Health Probe
# Evaluates local host and container dependencies: Docker, PHP extensions,
# Python/uv, Just, XeLaTeX, Biber, Zip/Unzip, SQLite database, and port bindings.
# ==============================================================================
set -eu

BOLD='\033[1m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

COUNT_OK=0
COUNT_WARN=0
COUNT_MISSING=0

print_row() {
    local component="$1"
    local status="$2"
    local detail="$3"

    local status_display=""
    case "$status" in
        OK)
            status_display="${GREEN}[OK]${NC}      "
            COUNT_OK=$((COUNT_OK + 1))
            ;;
        WARN)
            status_display="${YELLOW}[WARN]${NC}    "
            COUNT_WARN=$((COUNT_WARN + 1))
            ;;
        MISSING)
            status_display="${RED}[MISSING]${NC} "
            COUNT_MISSING=$((COUNT_MISSING + 1))
            ;;
    esac

    printf "%-26s %b %s\n" "$component" "$status_display" "$detail"
}

check_doctor_port() {
    local port="$1"
    local desc="$2"
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
            proc_info="system [privileged]"
        fi
    fi

    if [ -n "$proc_info" ]; then
        local pname
        pname=$(echo "$proc_info" | awk '{print $1}')
        local ppid
        ppid=$(echo "$proc_info" | awk '{print $2}')
        if [[ "$pname" =~ docker|dockerd|docker-proxy ]]; then
            print_row "Port $port ($desc)" "OK" "Bound by Docker ($pname PID $ppid)"
        else
            print_row "Port $port ($desc)" "WARN" "In use by $pname (PID $ppid) - port conflict"
        fi
    else
        print_row "Port $port ($desc)" "OK" "Available / Open"
    fi
}

echo ""
echo -e "${BOLD}========================================================================================${NC}"
echo -e "${BOLD}EIMS ENVIRONMENT HEALTH PROBE & SYSTEM DOCTOR${NC}"
echo -e "${BOLD}========================================================================================${NC}"
printf "%-26s %-12s %s\n" "Component" "Status" "Details / Version"
echo "----------------------------------------------------------------------------------------"

# 1. Docker Daemon
if docker info >/dev/null 2>&1; then
    DOCKER_VER="$(docker version --format '{{.Server.Version}}' 2>/dev/null || echo 'active')"
    print_row "Docker Daemon" "OK" "Docker Engine ${DOCKER_VER} (active)"
elif command -v docker >/dev/null 2>&1; then
    print_row "Docker Daemon" "WARN" "CLI installed, daemon inactive (run 'just fix-docker')"
else
    print_row "Docker Daemon" "MISSING" "Docker CLI not found"
fi

# 2. Docker Compose
if docker compose version >/dev/null 2>&1; then
    COMPOSE_VER="$(docker compose version --short 2>/dev/null || echo 'active')"
    print_row "Docker Compose" "OK" "Docker Compose ${COMPOSE_VER}"
else
    print_row "Docker Compose" "MISSING" "Docker Compose plugin missing"
fi

# 3. Host PHP CLI
if command -v php >/dev/null 2>&1; then
    PHP_VER="$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo 'detected')"
    print_row "Host PHP CLI" "OK" "PHP ${PHP_VER} (cli)"
else
    print_row "Host PHP CLI" "MISSING" "PHP CLI binary not found"
fi

# 4. PHP PDO Core
if command -v php >/dev/null 2>&1 && php -m 2>/dev/null | grep -qi '^pdo$'; then
    print_row "PHP PDO Core" "OK" "PDO abstraction layer active"
else
    print_row "PHP PDO Core" "MISSING" "PHP PDO extension missing"
fi

# 5. PHP PDO_SQLite
if command -v php >/dev/null 2>&1 && php -m 2>/dev/null | grep -qi '^pdo_sqlite$'; then
    print_row "PHP PDO_SQLite" "OK" "pdo_sqlite active (Offline fallback ready)"
else
    print_row "PHP PDO_SQLite" "MISSING" "pdo_sqlite missing (Offline fallback degraded)"
fi

# 6. PHP PDO_MySQL
if command -v php >/dev/null 2>&1 && php -m 2>/dev/null | grep -qi '^pdo_mysql$'; then
    print_row "PHP PDO_MySQL" "OK" "pdo_mysql active (Primary database ready)"
else
    print_row "PHP PDO_MySQL" "WARN" "pdo_mysql missing (host tests use SQLite only)"
fi

# 7. Python 3
if command -v python3 >/dev/null 2>&1; then
    PY_VER="$(python3 --version 2>&1)"
    print_row "Python 3" "OK" "${PY_VER}"
else
    print_row "Python 3" "MISSING" "Python 3 interpreter missing"
fi

# 8. Python uv
if command -v uv >/dev/null 2>&1; then
    UV_VER="$(uv --version 2>&1)"
    print_row "Python uv" "OK" "${UV_VER}"
else
    print_row "Python uv" "WARN" "uv not found (optional fast runner; pip/python3 available)"
fi

# 9. Just Task Runner
if command -v just >/dev/null 2>&1; then
    JUST_VER="$(just --version 2>&1)"
    print_row "Just Task Runner" "OK" "${JUST_VER}"
else
    print_row "Just Task Runner" "MISSING" "just binary missing"
fi

# 10. XeLaTeX Engine
if command -v xelatex >/dev/null 2>&1; then
    XELATEX_VER="$(xelatex --version 2>&1 | head -n1 | grep -o '3\.[0-9a-zA-Z.-]*' | head -n1 || echo 'active')"
    print_row "XeLaTeX Engine" "OK" "XeTeX ${XELATEX_VER} (for 'just paper')"
else
    print_row "XeLaTeX Engine" "MISSING" "XeLaTeX missing (needed for 'just paper')"
fi

# 11. latexmk Automation
if command -v latexmk >/dev/null 2>&1; then
    LATEXMK_VER="$(latexmk -v 2>&1 | grep -i 'version' | head -n1 | sed -e 's/^[[:space:]]*//' || echo 'active')"
    print_row "latexmk Automation" "OK" "${LATEXMK_VER}"
else
    print_row "latexmk Automation" "MISSING" "latexmk missing (needed for 'just paper')"
fi

# 12. Biber Bibliography
if command -v biber >/dev/null 2>&1; then
    BIBER_VER="$(biber --version 2>&1 | head -n1 || echo 'active')"
    print_row "Biber Bibliography" "OK" "${BIBER_VER}"
else
    print_row "Biber Bibliography" "MISSING" "biber missing (needed for citations in paper)"
fi

# 13. Zip Packaging Utility
if command -v zip >/dev/null 2>&1; then
    ZIP_VER="$(zip -v 2>&1 | head -n2 | tail -n1 | sed -e 's/^[[:space:]]*//' || echo 'active')"
    print_row "Zip Utility" "OK" "${ZIP_VER}"
else
    print_row "Zip Utility" "MISSING" "zip missing (needed for 'just package')"
fi

# 14. Unzip Utility
if command -v unzip >/dev/null 2>&1; then
    UNZIP_VER="$(unzip -v 2>&1 | head -n1 | awk '{print $1, $2}')"
    print_row "Unzip Utility" "OK" "${UNZIP_VER:-active}"
else
    print_row "Unzip Utility" "MISSING" "unzip missing"
fi

# 15. SQLite Database (src/database/eims.db)
DB_PATH="src/database/eims.db"
if [ -f "$DB_PATH" ]; then
    DB_SIZE="$(ls -lh "$DB_PATH" 2>/dev/null | awk '{print $5}' || echo 'unknown')"
    if [ -r "$DB_PATH" ] && [ -w "$DB_PATH" ]; then
        print_row "SQLite Database" "OK" "${DB_PATH} (${DB_SIZE}, read/write)"
    elif [ -r "$DB_PATH" ]; then
        print_row "SQLite Database" "WARN" "${DB_PATH} (${DB_SIZE}, read-only)"
    else
        print_row "SQLite Database" "WARN" "${DB_PATH} (permissions restricted)"
    fi
else
    print_row "SQLite Database" "WARN" "${DB_PATH} missing (provisioned via seed/migration)"
fi

# 16. Local Port Availability
check_doctor_port 80 "HTTP"
check_doctor_port 8080 "App"
check_doctor_port 3306 "MySQL"

echo "----------------------------------------------------------------------------------------"
TOTAL=$((COUNT_OK + COUNT_WARN + COUNT_MISSING))
echo -e "${BOLD}Summary: ${COUNT_OK}/${TOTAL} OK, ${COUNT_WARN} Warnings, ${COUNT_MISSING} Missing.${NC}"

if [[ "$COUNT_MISSING" -gt 0 ]]; then
    echo ""
    echo -e "${RED}[ACTION REQUIRED] Install missing dependencies to enable all subsystem workflows:${NC}"
    if command -v dnf >/dev/null 2>&1; then
        echo -e "  Fedora/RHEL: ${BOLD}sudo dnf install php-cli php-pdo php-mysqlnd php-sqlite3 texlive-xetex latexmk biber zip unzip${NC}"
    elif command -v apt-get >/dev/null 2>&1; then
        echo -e "  Debian/Ubuntu: ${BOLD}sudo apt update && sudo apt install -y php-cli php-sqlite3 php-mysql texlive-xetex latexmk biber zip unzip${NC}"
    elif command -v brew >/dev/null 2>&1; then
        echo -e "  macOS (Homebrew): ${BOLD}brew install php just latexmk biber zip unzip && brew install --cask mactex${NC}"
    fi
fi

if [[ "$COUNT_WARN" -gt 0 ]] && ! docker info >/dev/null 2>&1; then
    echo ""
    echo -e "${YELLOW}[NOTE] Docker daemon is currently inactive.${NC}"
    echo -e "  - Run ${BOLD}just fix-docker${NC} (or ${BOLD}sudo systemctl start docker${NC}) to start container services."
    echo -e "  - Running ${BOLD}just test${NC} automatically activates the host PHP fallback test suite."
fi

echo -e "${BOLD}========================================================================================${NC}"
echo ""

if [[ "$COUNT_MISSING" -gt 0 ]]; then
    exit 1
fi

exit 0

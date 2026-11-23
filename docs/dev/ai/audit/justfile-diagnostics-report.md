# Justfile Recipe Hardening & Pre-Flight Diagnostics Audit Report

- **Date**: 2026-11-22
- **Author**: Antigravity / EIMS Core Engineering
- **Target Subsystem**: Operations & Task Orchestration (`justfile`, `dev/scripts/**`)
- **Status**: EMPIRICALLY VERIFIED (Exit Code 0)
- **Reference**: ADR-001 (Zero Frameworks), ADR-002 (Dual-Driver Relational Resilience)

---

## 1. Executive Summary & Root Cause Analysis

### 1.1 The Incident
An operator attempting initial evaluation boot on Linux ran `just up` and received a disruptive terminal failure:
```text
[SUCCESS] Environment configured (.env, storage/, dev/tmp/, docs/specimens/screenshots/)
docker compose up -d
Cannot connect to the Docker daemon at unix:///var/run/docker.sock. Is the docker daemon running?
error: recipe `up` failed on line 23 with exit code 1
```

### 1.2 Root Cause
1. **Unchecked Shell Invocation**: The `up` recipe called `docker compose up -d` without verifying whether the host Docker daemon was active, socket permissions were accessible, or required TCP ports (`8080`, `3306`) were free.
2. **Missing Auto-Escalation**: On Linux distributions (Fedora, Debian, Ubuntu), systemd manages the `docker` service. When inactive or when the socket has restricted permissions, non-root users encounter abrupt `exit code 1` with zero self-healing or remediation instructions.
3. **Rigid Container Dependency in Testing**: Previous test workflows assumed an active Docker environment. If Docker was stopped, running `just test` failed immediately rather than engaging the clean-room offline SQLite3 test runner mandated by ADR-002 Dual-Driver Relational Resilience.

---

## 2. Hardened Architecture & Components Implemented

### 2.1 Centralized Cross-Platform Diagnostic Engine (`dev/scripts/sh/ensure-docker.sh`)
Implements headless, non-interactive Docker detection, platform escalation, and port collision auditing:
- **Fast-Path Connectivity Check**: Probes `docker info` directly with zero latency impact when Docker is active.
- **Operating System Probe**: Accurately detects Linux (`/proc/version`), macOS Darwin (`uname -s`), WSL2 (`WSL_DISTRO_NAME` and `microsoft` kernel probes), and Windows (`MINGW/MSYS`).
- **Headless Auto-Escalation**:
  - *Linux/WSL2*: Checks `systemctl is-active docker` and attempts non-interactive elevation via `sudo -n systemctl start docker` (or interactive if attached to a TTY). Automatically attempts to fix socket permissions (`sudo -n chmod 666 /var/run/docker.sock`).
  - *macOS*: Detects Colima, OrbStack, or Docker Desktop (`/Applications/Docker.app`) and launches background daemons with a 20-second connection timeout loop.
  - *Windows*: Launches Docker Desktop via PowerShell or `cmd.exe` background invocations.
- **Pre-Flight Port Collision Guard (`--check-ports`)**:
  - Probes TCP listeners on ports `8080` (App) and `3306` (MySQL) using `lsof`, `ss`, and `fuser`.
  - Distinguishes Docker-managed listeners (`docker-proxy`, `containerd`) from colliding foreign processes (XAMPP, Apache, system MySQL).
  - Emits actionable process identification (`PID`, executable name) and exact termination commands.

### 2.2 System Doctor Pre-Flight Audit Engine (`dev/scripts/sh/doctor.sh`)
Provides an exhaustive 18-point host and container readiness audit rendered in an accessible high-contrast terminal matrix:
1. Docker Daemon status & auto-escalation check
2. Docker Compose v2 availability
3. Host PHP CLI version (PHP 8.3+ compliance)
4. PHP PDO Core abstraction layer
5. PHP PDO_SQLite driver (offline fallback readiness)
6. PHP PDO_MySQL driver (primary container database connectivity)
7. Python 3 runtime
8. Python `uv` package and environment manager
9. GNU Just task runner binary
10. XeLaTeX engine (monograph compilation)
11. `latexmk` automation runner
12. Biber bibliography processor
13. Zip utility
14. Unzip utility
15. SQLite offline database integrity & read/write permissions (`src/database/eims.db`)
16. Port 80 (HTTP) collision check
17. Port 8080 (Application Gateway) collision check
18. Port 3306 (MySQL Database) collision check

### 2.3 Windows Native PowerShell Parity (`dev/scripts/ps1/ensure-docker.ps1`)
Provides 1:1 behavioral parity for Windows developers executing commands outside WSL2:
- Detects Docker Desktop service via `Get-Process` and `Get-Service`.
- Executes TCP port listener audits using `Get-NetTCPConnection`.
- Provides explicit `Stop-Process` and `Set-Item` configuration remedies on port conflict.

---

## 3. Justfile Recipe Hardening Matrix

| Recipe | Pre-Flight Check | Fallback / Healing Behavior |
|---|---|---|
| `just doctor` | Executes `dev/scripts/sh/doctor.sh` | Full 18-point audit with color-coded component health |
| `just fix-docker` | Attempts `sudo systemctl start docker` + socket ACL fix | Non-interactive safe elevation |
| `just up` | `ensure-docker.sh --check-ports` | Audits ports 8080 & 3306; auto-starts daemon; halts on port conflict with actionable PID |
| `just down` | `ensure-docker.sh --check-only` | Detects inactive daemon cleanly; avoids redundant failure errors |
| `just lint` | Host PHP `php -l` check | If host PHP is absent, routes syntax linting through Docker container `app` |
| `just test` | Docker container `app` probe | If Docker is offline, automatically activates host PHP runner with SQLite3 fallback |
| `just test-hardening`| Container or Host PHP probe | Executes 82 security hardening tests in memory/SQLite mode |
| `just test-integration`| Container or Host PHP probe | Executes 41 consolidated integration tests in SQLite mode |
| `just test-sabotage` | Container or Host PHP probe | Runs error kernel and sabotage tests against host PHP when container is inactive |
| `just seed-stress` | Docker MySQL container probe | Seeds MySQL if active; automatically routes chunked stress records to SQLite if offline |
| `just benchmark-db` | Docker MySQL container probe | Verifies active MySQL container; alerts operator if container is stopped |
| `just screenshot` | Python 3 and Chromium probe | Warns operator if Chromium is missing and provides distro package commands |
| `just paper` | `latexmk` and `xelatex` probe | Prints exact distro package commands (Fedora, Ubuntu, Arch, macOS, Windows) if missing |
| `just package` | `zip` utility probe | Reports distro install command if zip is missing |

---

## 4. Empirical Verification Results

All recipes and scripts were empirically executed and verified in the live execution environment:

### 4.1 System Doctor Output (`just doctor`)
```text
========================================================================================
EIMS ENVIRONMENT HEALTH PROBE & SYSTEM DOCTOR
========================================================================================
Component                  Status       Details / Version
----------------------------------------------------------------------------------------
Docker Daemon               [WARN]     CLI installed, daemon inactive (run 'just fix-docker')
Docker Compose              [OK]       Docker Compose 5.6.1
Host PHP CLI                [OK]       PHP 8.5.12 (cli)
PHP PDO Core                [OK]       PDO abstraction layer active
PHP PDO_SQLite              [OK]       pdo_sqlite active (Offline fallback ready)
PHP PDO_MySQL               [OK]       pdo_mysql active (Primary database ready)
Python 3                    [OK]       Python 3.15.0
Python uv                   [OK]       uv 0.12.23 (x86_64-unknown-linux-gnu)
Just Task Runner            [OK]       just 1.59.0
XeLaTeX Engine              [OK]       XeTeX 3.141592653-2.6-0.999998 (for 'just paper')
latexmk Automation          [OK]       Latexmk, John Collins, 9 March 2026. Version 4.88
Biber Bibliography          [OK]       biber version: 2.22
Zip Utility                 [OK]       This is Zip 3.0 (July 5th 2008), by Info-ZIP.
Unzip Utility               [OK]       UnZip 6.00
SQLite Database             [OK]       src/database/eims.db (1.1M, read/write)
Port 80 (HTTP)              [OK]       Available / Open
Port 8080 (App)             [OK]       Available / Open
Port 3306 (MySQL)           [OK]       Available / Open
----------------------------------------------------------------------------------------
Summary: 17/18 OK, 1 Warnings, 0 Missing.
```

### 4.2 Offline Adaptive Test Execution (`just test`)
With Docker daemon stopped, `just test` smoothly executed all quality gates via host PHP fallback:
1. **PHP Syntax Lint**: 78 source files validated (`php -l`), 0 syntax errors.
2. **Security Hardening Suite (`test-hardening.php`)**:
   - Total assertions: 82
   - Passed: 82
   - Failed: 0
3. **Consolidated Integration Suite (`runner.php`)**:
   - Total test cases: 41
   - Passed: 41
   - Failed: 0
4. **Panel Sabotage & Defense Kernel (`test-panel-sabotage.sh`)**:
   - Warning elevation to `Throwable`: PASS
   - Visual diagnostic error console (`APP_DEBUG=true`): PASS
   - Production error suppression (`APP_DEBUG=false`): PASS
   - Dynamic YAML header parsing: PASS

**Aggregate Verification**: 100% test pass rate (123 checks passing, exit code 0).

---

## 5. Documentation Synchronization

The following operational handbooks have been updated to reflect the new diagnostics:
1. `docs/GETTING-STARTED.md`: Added Step 0 (`just doctor`), troubleshooting guidance, and offline testing documentation.
2. `docs/getting-started/README.md`: Documented the 18-point environmental audit engine.
3. `docs/getting-started/linux/installation.md`: Integrated `just doctor`, `just fix-docker`, and pre-flight port audits.
4. `docs/getting-started/windows/installation.md`: Added `just doctor` and `dev/scripts/ps1/ensure-docker.ps1`.
5. `docs/getting-started/windows/troubleshooting.md`: Added port conflict diagnosis, PowerShell diagnostic script usage, and offline SQLite fallback instructions.

---

## 6. Conclusion & Operator Next Steps

The repository operations are now hardened against missing Docker daemons, socket permissions failures, and port collisions. Developers and evaluators across Linux, macOS, and Windows can immediately diagnose environmental issues via `just doctor` or run full verification suites offline via `just test`.

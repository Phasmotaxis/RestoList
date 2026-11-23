# Windows Troubleshooting & Common PHP Errors

- **Root Hub**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Handbook Index**: [Getting Started Handbook](../README.md)
- **Prerequisites**: [Windows Prerequisites](prerequisites.md)
- **WSL2 Setup**: [WSL2 Configuration](wsl2-setup.md)
- **AI Prompts**: [AI Troubleshooting Prompts](ai-troubleshooting-prompts.md)

---

## Document Purpose & Audience
- **What this is for**: Explains common failure modes on Windows (CRLF corruption, Docker socket errors, port conflicts) and PHP strict typing errors.
- **Who reads this**: Students and developers troubleshooting issues on Windows/WSL2.
- **Last verified**: 2026-11-19 (UNVERIFIED: current host is Linux x86_64; Windows/Docker Desktop troubleshooting steps were not executed directly in this environment).

---

## Core Definitions
- **Terminal**: The command window (WSL2 Ubuntu shell) where commands and error outputs appear.
- **Container**: The Docker services whose network ports and volumes are being diagnosed.
- **Environment variable**: Configuration settings in `.env` (such as `APP_PORT`) that can be altered to avoid port conflicts.

---

## 1. Common Windows / WSL2 Disruptions

### Quick Diagnostic Audit
Before manual troubleshooting, run the unified environment diagnostic tool:
```bash
just doctor
```
Or run the Windows PowerShell diagnostic script from PowerShell:
```powershell
powershell -ExecutionPolicy Bypass -File dev\scripts\ps1\ensure-docker.ps1 -CheckPorts
```

### Error: `\r: command not found` or `syntax error near unexpected token $'\r'`
- **Cause**: Files were saved with Windows CRLF line endings instead of UNIX LF.
- **Fix**: Run `dos2unix` or convert files via Git:
  ```bash
  sudo apt install -y dos2unix
  find dev/scripts -type f -name "*.sh" -exec dos2unix {} +
  ```

### Error: `Cannot connect to the Docker daemon at unix:///var/run/docker.sock`
- **Cause**: Docker Desktop is not running or WSL2 integration is disabled.
- **Fix**: Launch Docker Desktop in Windows. Go to Settings -> Resources -> WSL Integration -> Ensure Ubuntu is toggled ON, then click "Apply & restart".
- **Offline Alternative**: EIMS supports 100% offline development and testing. Running `just test` automatically activates the host PHP runner with SQLite3 relational fallback (ADR-002) without requiring Docker.

### Error: Port 8080 or 3306 already in use
- **Cause**: Another service (XAMPP, WampServer, or local MySQL) is running on Windows. `just up` will detect this during pre-flight port auditing and output the conflicting process PID.
- **Fix**: Edit `.env` to change exposed ports:
  ```env
  APP_PORT=8085
  DB_PORT=3307
  ```
  Then rebuild: `just down && just up`. Or stop the conflicting Windows process via PowerShell:
  ```powershell
  Stop-Process -Id <PID> -Force
  ```

---

## 2. Common PHP Syntax Errors Explained

### 1. `ParseError: syntax error, unexpected token ";"`
- **Meaning**: A closing semicolon was placed where an expression or function argument was expected.
- **Example**:
  ```php
  // BAD
  $user = Database::executeQuery(;);

  // GOOD
  $user = Database::executeQuery('SELECT * FROM users;');
  ```

### 2. `TypeError: Return value must be of type X, null returned`
- **Meaning**: `declare(strict_types=1);` is enforced. A function promised to return an object (like `PDO`), but returned `null` on failure.
- **Example**:
  ```php
  // BAD
  function getConn(): PDO {
      if (!$ready) return null; // CRASH
  }

  // GOOD
  function getConn(): ?PDO { // Allow nullable
      if (!$ready) return null;
  }
  ```

### 3. `PDOException: SQLSTATE[HY000] [2002] Connection refused`
- **Meaning**: The PHP app cannot reach MySQL at `db:3306`.
- **Resolution**: EIMS automatically falls back to SQLite3 without crashing. If you need MySQL, check container state with `docker compose ps` and logs with `docker compose logs db`.

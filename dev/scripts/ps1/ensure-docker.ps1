# ==============================================================================
# EIMS Docker Engine Windows Pre-Flight & Diagnostics Controller (PowerShell)
# Cavite State University - DICT24 Information Management
# ==============================================================================

[CmdletBinding()]
param (
    [switch]$CheckPorts,
    [int[]]$Ports = @(8080, 3306),
    [switch]$CheckOnly,
    [switch]$Quiet,
    [switch]$Fix
)

$ErrorActionPreference = "Continue"

function Write-DoctorHeader {
    param([string]$Text)
    if (-not $Quiet) {
        Write-Host "`n=== $Text ===" -ForegroundColor Cyan
    }
}

# 1. Port collision check
if ($CheckPorts) {
    $collision = $false
    foreach ($port in $Ports) {
        $conn = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($conn) {
            $proc = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
            $procName = if ($proc) { $proc.ProcessName } else { "Unknown" }
            $procId = if ($proc) { $proc.Id } else { $conn.OwningProcess }

            if ($procName -match "docker|com\.docker|containerd") {
                if (-not $Quiet) {
                    Write-Host "[INFO] Port $port is managed by Docker ($procName, PID $procId)." -ForegroundColor Cyan
                }
            } else {
                Write-Host "[ERROR] Port collision detected: Port $port is already in use by '$procName' (PID $procId)!" -ForegroundColor Red
                Write-Host "Actionable fix:"
                Write-Host "  Stop conflicting process: Stop-Process -Id $procId -Force"
                Write-Host "  Or stop Windows service:   Stop-Service -Name $procName"
                Write-Host "  Or override port in .env: Set APP_PORT=<free_port> or DB_PORT=<free_port>"
                $collision = $true
            }
        } else {
            if (-not $Quiet) {
                Write-Host "[INFO] Port $port is free and available." -ForegroundColor Green
            }
        }
    }
    if ($collision) {
        Write-Error "Aborting container startup due to port collision."
        exit 1
    }
}

# 2. Check Docker CLI
$dockerCmd = Get-Command docker -ErrorAction SilentlyContinue
if (-not $dockerCmd) {
    Write-Host "[ERROR] Docker CLI binary 'docker' not found in PATH." -ForegroundColor Red
    Write-Host "Actionable installation instructions:"
    Write-Host "  Download and install Docker Desktop for Windows: https://docs.docker.com/desktop/setup/install/windows-install/"
    Write-Host "  Or install via Winget: winget install Docker.DockerDesktop"
    exit 1
}

# 3. Check Docker daemon responsiveness
$null = & docker info 2>&1
if ($LASTEXITCODE -eq 0) {
    if (-not $Quiet) {
        Write-Host "[OK] Docker daemon is active and responsive." -ForegroundColor Green
    }
    exit 0
}

if ($CheckOnly) {
    exit 1
}

Write-Host "[WARN] Docker daemon is not responding." -ForegroundColor Yellow
Write-Host "[INFO] Initiating Windows Docker Desktop auto-escalation..." -ForegroundColor Cyan

# 4. Attempt to launch Docker Desktop
$dockerPaths = @(
    "${env:ProgramFiles}\Docker\Docker\Docker Desktop.exe",
    "${env:LOCALAPPDATA}\Docker\Docker Desktop.exe",
    "C:\Program Files\Docker\Docker\Docker Desktop.exe"
)

$dockerExe = $null
foreach ($path in $dockerPaths) {
    if (Test-Path $path) {
        $dockerExe = $path
        break
    }
}

if ($dockerExe) {
    Write-Host "[INFO] Launching Docker Desktop: $dockerExe..." -ForegroundColor Cyan
    Start-Process -FilePath $dockerExe
    Write-Host "[INFO] Waiting up to 30 seconds for Docker engine to become responsive..." -ForegroundColor Cyan
    $waited = 0
    $started = $false
    while ($waited -lt 30) {
        Start-Sleep -Seconds 3
        $waited += 3
        $null = & docker info 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "[OK] Docker Desktop started and verified responsive." -ForegroundColor Green
            $started = $true
            exit 0
        }
    }
    if (-not $started) {
        Write-Host "[ERROR] Docker Desktop did not respond within 30 seconds." -ForegroundColor Red
        Write-Host "Actionable fix: Check Docker Desktop status in the Windows system tray."
    }
} else {
    Write-Host "[ERROR] Docker Desktop executable not found at standard installation paths." -ForegroundColor Red
    Write-Host "Actionable fix:"
    Write-Host "  1. Start Docker Desktop manually from the Windows Start menu."
    Write-Host "  2. If using WSL2, ensure WSL Integration is enabled in Docker Desktop:"
    Write-Host "     Settings -> Resources -> WSL Integration -> Enable your distro."
    Write-Host "  3. Note: EIMS supports offline testing! Run 'just test' for host PHP fallback."
}

exit 1

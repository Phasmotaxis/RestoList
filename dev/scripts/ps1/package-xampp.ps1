# ==============================================================================
# DICT24 Restaurant ERP: Windows PowerShell XAMPP Distribution Packaging Script
# ==============================================================================

[CmdletBinding()]
param ()

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$RepoRoot = Resolve-Path "$ScriptDir\..\..\.."
$DistDir = "$RepoRoot\dist"
$StageDir = "$RepoRoot\dev\tmp\xampp-build"
$ZipTarget = "$DistDir\dict24-resto-erp-xampp.zip"
$SqlSource = "$RepoRoot\database\dict24_resto_erp.sql"

Write-Host "[INFO] === Starting DICT24 Restaurant ERP XAMPP Packaging (PowerShell) ===" -ForegroundColor Cyan

# 1. Ensure target directories
New-Item -ItemType Directory -Force -Path $DistDir | Out-Null
if (Test-Path $StageDir) {
    Remove-Item -Recurse -Force $StageDir
}
New-Item -ItemType Directory -Force -Path $StageDir | Out-Null

# 2. Stage application files
Write-Host "[INFO] Staging application source and assets..." -ForegroundColor Gray
New-Item -ItemType Directory -Force -Path "$StageDir\app" | Out-Null
New-Item -ItemType Directory -Force -Path "$StageDir\public" | Out-Null
New-Item -ItemType Directory -Force -Path "$StageDir\storage" | Out-Null
New-Item -ItemType Directory -Force -Path "$StageDir\database" | Out-Null

Copy-Item -Recurse -Force "$RepoRoot\src\app\*" "$StageDir\app\"
Copy-Item -Recurse -Force "$RepoRoot\src\public\*" "$StageDir\public\"

if (Test-Path $SqlSource) {
    Copy-Item -Force $SqlSource "$StageDir\database\dict24_resto_erp.sql"
} else {
    Write-Warning "SQL schema $SqlSource not found; creating placeholder."
    Set-Content -Path "$StageDir\database\dict24_resto_erp.sql" -Value "-- Run migrations via database/migrations/*.sql"
}

# 3. Create .env configuration
@"
# DICT24 Restaurant ERP - XAMPP Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost/dict24-resto-erp/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=eims_db
DB_USER=root
DB_PASS=
DB_SQLITE_PATH=storage/fallback.sqlite
"@ | Set-Content -Path "$StageDir\.env"

Copy-Item "$StageDir\.env" "$StageDir\.env.example"

# 4. Create zip archive
Write-Host "[INFO] Creating zip archive at $ZipTarget..." -ForegroundColor Gray
if (Test-Path $ZipTarget) {
    Remove-Item -Force $ZipTarget
}
Compress-Archive -Path "$StageDir\*" -DestinationPath $ZipTarget -Force

if (Test-Path $ZipTarget) {
    $zipItem = Get-Item $ZipTarget
    $sizeMb = [math]::Round($zipItem.Length / 1MB, 2)
    Write-Host "[SUCCESS] Packaged $ZipTarget ($sizeMb MB)" -ForegroundColor Green
} else {
    Write-Error "Failed to produce $ZipTarget"
}

# 5. Cleanup
Remove-Item -Recurse -Force $StageDir
Write-Host "[SUCCESS] PowerShell Packaging Complete." -ForegroundColor Green

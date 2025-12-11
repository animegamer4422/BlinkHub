Write-Host "BlinkHub setup script starting..." -ForegroundColor Green

$ErrorActionPreference = "Stop"

# ---------------- CONFIG ----------------
$ScoopRoot  = "$env:USERPROFILE\scoop"
$phpApp     = "php"
$mysqlApp   = "mysql"

$dbName     = "blinkit_clone"
$mysqlUser  = "root"
$mysqlPass  = "12345678"   # change if you use a different root password
$schemaFile = Join-Path $PSScriptRoot "blinkit_clone_schema.sql"

# ---------------- 1. INSTALL SCOOP ----------------
if (-not (Get-Command scoop -ErrorAction SilentlyContinue)) {
    Write-Host "Scoop not found. Installing Scoop..." -ForegroundColor Yellow

    try {
        Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force
    } catch {
        Write-Host "Could not change execution policy (might already be fine). Continuing..." -ForegroundColor DarkYellow
    }

    Invoke-RestMethod -UseBasicParsing get.scoop.sh | Invoke-Expression

    Write-Host "Scoop installed." -ForegroundColor Green
} else {
    Write-Host "Scoop already installed ✅" -ForegroundColor Green
}

# ---------------- 2. INSTALL PHP & MYSQL ----------------
Write-Host "Installing PHP and MySQL via Scoop (if not already installed)..." -ForegroundColor Cyan

scoop install $phpApp -g 2>$null
scoop install $mysqlApp -g 2>$null

Write-Host "Scoop install step completed (PHP/MySQL)." -ForegroundColor Green

# ---------------- 3. RESOLVE PATHS ----------------
$phpExe    = Join-Path $ScoopRoot "apps\php\current\php.exe"
$mysqlDir  = Join-Path $ScoopRoot "apps\mysql\current\bin"
$mysqlExe  = Join-Path $mysqlDir "mysql.exe"
$mysqldExe = Join-Path $mysqlDir "mysqld.exe"

if (!(Test-Path $phpExe)) {
    Write-Host "ERROR: php.exe not found at $phpExe" -ForegroundColor Red
    Write-Host "Check: scoop list and ensure php is installed correctly."
    exit 1
}

if (!(Test-Path $mysqlExe -and (Test-Path $mysqldExe))) {
    Write-Host "ERROR: mysql.exe or mysqld.exe not found under $mysqlDir" -ForegroundColor Red
    Write-Host "Check: scoop list and ensure mysql is installed correctly."
    exit 1
}

Write-Host "PHP found at: $phpExe" -ForegroundColor Green
Write-Host "MySQL client found at: $mysqlExe" -ForegroundColor Green
Write-Host "MySQL server (mysqld) found at: $mysqldExe" -ForegroundColor Green

# ---------------- 4. START MYSQL SERVER ----------------
Write-Host "Starting MySQL server (mysqld)..." -ForegroundColor Yellow
Start-Process -FilePath $mysqldExe -WorkingDirectory $mysqlDir | Out-Null

Write-Host "Waiting for MySQL to start..." -ForegroundColor Yellow
Start-Sleep -Seconds 6

# ---------------- 5. CREATE DB & IMPORT SCHEMA ----------------
if (!(Test-Path $schemaFile)) {
    Write-Host "ERROR: Schema file not found at $schemaFile" -ForegroundColor Red
    Write-Host "Place blinkit_clone_schema.sql in the project root and re-run setup."
    exit 1
}

Write-Host "Checking if database '$dbName' exists..." -ForegroundColor Cyan

$dbCheck = & $mysqlExe -u $mysqlUser -p$mysqlPass -N -e "SHOW DATABASES LIKE '$dbName';" 2>$null

if (-not $dbCheck) {
    Write-Host "Database '$dbName' does not exist. Creating..." -ForegroundColor Yellow

    & $mysqlExe -u $mysqlUser -p$mysqlPass -e "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>$null

    Write-Host "Importing schema from '$schemaFile'..." -ForegroundColor Yellow

    # Use cmd with redirection to avoid PowerShell quoting pain
    $schemaPath = ($schemaFile -replace '\\','/')
    $mysqlCmd   = "`"$mysqlExe`" -u $mysqlUser -p$mysqlPass $dbName < `"$schemaPath`""

    $result = cmd /c $mysqlCmd 2>&1

    if ($LASTEXITCODE -eq 0) {
        Write-Host "Database '$dbName' created and schema imported successfully ✅" -ForegroundColor Green
    } else {
        Write-Host "Schema import failed. MySQL output:" -ForegroundColor Red
        Write-Host $result
        exit 1
    }
} else {
    Write-Host "Database '$dbName' already exists. Skipping creation/import. ✅" -ForegroundColor Green
}

Write-Host ""
Write-Host "Setup complete! 🎉" -ForegroundColor Green
Write-Host "You can now use your existing start.ps1 to run the dev environment."
Write-Host "DB Name : $dbName"
Write-Host "MySQL   : $mysqlUser / $mysqlPass"

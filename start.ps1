Write-Host "Starting BlinkHub development environment..." -ForegroundColor Green

# ----- CONFIG -----
$phpPort    = 8000
$phpFolder  = "$PSScriptRoot\public"
$phpExe     = "$HOME\scoop\apps\php\current\php.exe"
$mysqlExe   = "$HOME\scoop\apps\mysql\current\bin\mysql.exe"
$projectUrl = "http://localhost:$phpPort"
$dbName     = "blinkit_clone"

# ----- CHECK PHP -----
if (!(Test-Path $phpExe)) {
    Write-Host "PHP not found. Install with: scoop install php" -ForegroundColor Red
    exit
}

Write-Host "PHP detected" -ForegroundColor Green

# ----- CHECK MYSQL -----
if (!(Test-Path $mysqlExe)) {
    Write-Host "MySQL not found. Install with: scoop install mysql" -ForegroundColor Red
} else {
    Write-Host "MySQL detected" -ForegroundColor Green

Start-Process mysqld
}

# ----- START PHP SERVER -----
Write-Host "Starting PHP server at $projectUrl" -ForegroundColor Cyan

# Start php -S in a new window without crazy quoting
Start-Process -FilePath $phpExe `
    -ArgumentList @("-S", "localhost:$phpPort", "-t", $phpFolder) `
    -WorkingDirectory $phpFolder

Start-Sleep -Seconds 1

# ----- OPEN IN BROWSER -----
Write-Host "Opening browser..." -ForegroundColor Cyan
Start-Process $projectUrl

# ----- DONE -----
Write-Host ""
Write-Host "BlinkHub is now running!" -ForegroundColor Green
Write-Host ("Public Folder: " + $phpFolder)
Write-Host ("URL: " + $projectUrl)
Write-Host "MySQL: root / 12345678"


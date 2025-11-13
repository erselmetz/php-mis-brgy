@echo off
REM ============================================================
REM  MySQL setup for Laragon v6 (Repeatable version - Fixed)
REM  - Works even if root already has a password
REM  - Sets new root password (Root@2025)
REM  - Creates admin / phpmisbrgy account
REM ============================================================

set NEW_ROOT_PASSWORD=Root@2025
set ADMIN_USERNAME=admin
set ADMIN_PASSWORD=phpmisbrgy

REM === Detect Laragon MySQL path ===
set MYSQL_PATH="%laragon_dir%\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"
if "%laragon_dir%"=="" (
    set MYSQL_PATH="C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"
)

echo.
echo ===========================================
echo   Configuring MySQL in Laragon (Repeatable)
echo ===========================================
echo.

echo Testing MySQL root connection without password...
%MYSQL_PATH% -u root -e "SELECT 'Connected without password';" >nul 2>&1

if %errorlevel%==0 (
    echo ✅ Connected without password.
    set MYSQL_LOGIN=%MYSQL_PATH% -u root
) else (
    echo ⚠️ Root requires password.
    set /p ROOT_PASS="Enter current root password: "
    set MYSQL_LOGIN=%MYSQL_PATH% -u root -p%ROOT_PASS%
)

echo.
echo Applying changes...

%MYSQL_LOGIN% -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '%NEW_ROOT_PASSWORD%'; FLUSH PRIVILEGES; CREATE USER IF NOT EXISTS '%ADMIN_USERNAME%'@'localhost' IDENTIFIED BY '%ADMIN_PASSWORD%'; GRANT ALL PRIVILEGES ON *.* TO '%ADMIN_USERNAME%'@'localhost' WITH GRANT OPTION; FLUSH PRIVILEGES;"

if %errorlevel%==0 (
    echo.
    echo ✅ MySQL setup completed successfully!
    echo.
    echo Root password: %NEW_ROOT_PASSWORD%
    echo New account:  %ADMIN_USERNAME% / %ADMIN_PASSWORD%
) else (
    echo.
    echo ❌ MySQL configuration failed.
    echo Make sure MySQL is running in Laragon.
)

pause

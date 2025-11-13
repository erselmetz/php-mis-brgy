@echo off
REM ============================================================
REM  MySQL Backup Script for Laragon v6
REM ============================================================

REM >>> CONFIGURATION
set DB_USER=root
set DB_PASS=Root@2025
set BACKUP_DIR=C:\laragon\backups
set MYSQLDUMP_PATH="C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe"
set MYSQL_PATH="C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"

REM >>> CREATE BACKUP DIRECTORY IF NOT EXISTS
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

REM >>> GET DATE (YYYY-MM-DD format)
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set dt=%%I
set DATESTAMP=%dt:~0,4%-%dt:~4,2%-%dt:~6,2%_%dt:~8,2%-%dt:~10,2%

REM >>> BACKUP ALL DATABASES
set BACKUP_FILE=%BACKUP_DIR%\mysql_backup_%DATESTAMP%.sql

echo.
echo ===========================================
echo   Backing up all MySQL databases
echo ===========================================
echo.

%MYSQLDUMP_PATH% -u %DB_USER% -p%DB_PASS% --all-databases --routines --events --single-transaction > "%BACKUP_FILE%"

if %errorlevel%==0 (
    echo ✅ Backup successful!
    echo Saved to: %BACKUP_FILE%
) else (
    echo ❌ Backup failed. Please check if MySQL is running and credentials are correct.
)

pause

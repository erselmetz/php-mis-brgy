@echo off
REM =====================================================
REM Database SQL Generator for MIS Barangay
REM =====================================================
REM This batch file regenerates database_complete.sql
REM by processing all schema PHP files
REM
REM Usage: Double-click this file or run from command line
REM        schema\generate_sql.bat
REM =====================================================

setlocal enabledelayedexpansion

REM Get the directory where this batch file is located
set SCHEMA_DIR=%~dp0
set PHP_PATH=php
set OUTPUT_FILE=%SCHEMA_DIR%database_complete.sql

echo.
echo =====================================================
echo   MIS Barangay - Database SQL Generator
echo =====================================================
echo.
echo Schema Directory: %SCHEMA_DIR%
echo Output File: %OUTPUT_FILE%
echo.

REM Check if PHP is available
where %PHP_PATH% >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo.
    echo Please make sure Laragon or PHP is properly installed.
    echo.
    pause
    exit /b 1
)

echo [*] PHP found: checking version...
%PHP_PATH% --version
echo.

REM Run the PHP script that generates SQL
echo [*] Generating database_complete.sql...
echo.

%PHP_PATH% "%SCHEMA_DIR%generate_sql.php"

if %errorlevel% neq 0 (
    echo.
    echo ERROR: Failed to generate SQL file
    echo.
    pause
    exit /b 1
)

echo.
echo =====================================================
echo   SUCCESS: database_complete.sql has been generated!
echo =====================================================
echo.
echo The file is ready to use. You can now:
echo 1. Copy the SQL file content to your database tool
echo 2. Use it to create or recreate your database
echo.
echo File location: %OUTPUT_FILE%
echo.
pause

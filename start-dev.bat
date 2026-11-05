@echo off
setlocal
title Foxream - dev server
cd /d "%~dp0"

rem Runs the site without opening Laragon, using Laragon's PHP binary.
rem The database is SQLite (database\database.sqlite) by default, so
rem nothing else is needed. MySQL is only started when .env says
rem DB_CONNECTION=mysql.
set "PHP=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
set "MYSQL_DIR=C:\laragon\bin\mysql\mysql-8.4.3-winx64"
set "PORT=8001"

if not exist "%PHP%" (
    echo [X] PHP tidak ditemukan: %PHP%
    pause
    exit /b 1
)

findstr /b /c:"DB_CONNECTION=mysql" .env >nul
if errorlevel 1 goto server

netstat -ano | findstr /r /c:":3306 .*LISTENING" >nul
if not errorlevel 1 goto server

echo [..] Menyalakan MySQL...
start "MySQL - Foxream" /min "%MYSQL_DIR%\bin\mysqld.exe" --defaults-file="%MYSQL_DIR%\my.ini" --standalone
rem Wait up to ~30s (ping = 1-second sleep that works even without a console).
for /l %%i in (1,1,30) do (
    ping -n 2 127.0.0.1 >nul
    netstat -ano | findstr /r /c:":3306 .*LISTENING" >nul && goto server
)
echo [X] MySQL gagal menyala dalam 30 detik.
pause
exit /b 1

:server
echo [..] Website: http://localhost:%PORT%   (Ctrl+C untuk berhenti)
start "" cmd /c "ping -n 4 127.0.0.1 >nul & start http://localhost:%PORT%"
"%PHP%" artisan serve --host=0.0.0.0 --port=%PORT%

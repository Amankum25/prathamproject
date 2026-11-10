@echo off
title RC Campus Connect - P27 Bill Tracking System Launcher
color 70

echo =====================================================================
echo    RC CAMPUS CONNECT · MODULE P27: BILL TRACKING SYSTEM
echo    Ramanujan College · University of Delhi · GE-5b
echo    Student: Pratham ^| Roll No: 24020568036
echo =====================================================================
echo.

echo [1/3] Checking XAMPP services...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo   [OK] MySQL is already running.
) else (
    echo   [..] Starting MySQL in background...
    start "" /B "C:\xampp\mysql_start.bat"
    timeout /t 2 /nobreak >nul
)

tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo   [OK] Apache is already running.
) else (
    echo   [..] Starting Apache in background...
    start "" /B "C:\xampp\apache_start.bat"
    timeout /t 2 /nobreak >nul
)

echo.
echo [2/3] Running official check_module.php tool...
"C:\xampp\php\php.exe" "C:\xampp\htdocs\p27-24020568036\tools\check_module.php"

echo.
echo [3/3] Opening project in default browser...
start "" "http://localhost/p27-24020568036/index.php"

echo.
echo =====================================================================
echo   Project is live at: http://localhost/p27-24020568036/
echo   Press any key to close this launcher window.
echo =====================================================================
pause >nul

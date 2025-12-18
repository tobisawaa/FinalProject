@echo off
REM Quick launcher for push notification tools
REM Place this file in c:\laragon\www\Final Projek\tools\

set PHP_PATH=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe
set PROJECT_PATH=C:\laragon\www\Final Projek

echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║  🔔 WEATHER ALERT PUSH NOTIFICATION LAUNCHER               ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Choose action:
echo 1. Run Weather Alerts (send notifications)
echo 2. Verify Setup (check configuration)
echo 3. View Database Subscriptions
echo 4. Exit
echo.

set /p choice="Enter choice (1-4): "

if "%choice%"=="1" (
    echo.
    echo Running weather alert checker...
    "%PHP_PATH%" "%PROJECT_PATH%\tools\run_alerts.php"
    echo.
    echo ✅ Alert check completed!
    pause
    goto end
)

if "%choice%"=="2" (
    echo.
    echo Verifying setup...
    "%PHP_PATH%" "%PROJECT_PATH%\tools\verify_setup.php"
    pause
    goto end
)

if "%choice%"=="3" (
    echo.
    echo Subscriptions count:
    "%PHP_PATH%" -r "require 'config/database.php'; require 'src/classes/Database.php'; $db = Database::getInstance(); $count = $db->fetchOne('SELECT COUNT(*) as cnt FROM push_subscriptions'); echo 'Total subscriptions: ' . $count['cnt'] . PHP_EOL;"
    pause
    goto end
)

if "%choice%"=="4" (
    exit /b 0
)

echo Invalid choice!
pause

:end

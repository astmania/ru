@echo off
REM Simple batch file to start Laravel server with PHP 8.4 (or 8.3 as fallback)
REM Usage: START.bat

set PHP84=C:\OSPanel\modules\php\PHP_8.4\php.exe
set PHP83=C:\OSPanel\modules\php\PHP_8.3\php.exe

REM Check for PHP 8.4 first (required by dependencies)
if exist "%PHP84%" (
    set PHP=%PHP84%
    set PHPVER=8.4
    goto :start
)

REM Fallback to PHP 8.3 (may cause dependency issues)
if exist "%PHP83%" (
    set PHP=%PHP83%
    set PHPVER=8.3
    echo WARNING: PHP 8.3 detected. Dependencies require PHP 8.4!
    echo Please install PHP 8.4 in OSPanel to avoid compatibility issues.
    echo.
    goto :start
)

echo ERROR: PHP not found!
echo Please install PHP 8.4 (or PHP 8.3) in OSPanel
pause
exit /b 1

:start
echo Starting Laravel server with PHP %PHPVER%...
echo.

echo Checking PHP version...
"%PHP%" -v
echo.

echo Building frontend assets...
call npm run build
echo.

echo Starting Laravel development server...
echo Server will be available at: http://localhost:8000
echo Press Ctrl+C to stop the server
echo.

"%PHP%" artisan migrate
"%PHP%" artisan db:seed
"%PHP%" artisan user:assign-role kenbayev94@mail.ru --moderator

REM Создаем storage:link только если еще нет public\storage
if not exist "public\storage" (
    "%PHP%" artisan storage:link
)

"%PHP%" artisan serve
pause

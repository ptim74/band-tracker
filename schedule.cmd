@echo off

NET SESSION > NUL 2>&1
IF %ERRORLEVEL% NEQ 0 (
    powershell "start-process ""%0"" -verb runas"
    goto :eof
) 

:ask_php

echo.
echo Enter the directory where PHP is installed.
echo.
set /p PHP_DIR=PHP_DIR:

if exist "%PHP_DIR%\php.exe" goto php_done

echo.
echo php.exe not found from %PHP_DIR%

goto ask_php

:php_done

echo cd /d "%~dp0" > "%~dp0run.cmd"
echo "%php_dir%\php.exe" tracker.php ^>^> tracker.log 2^>^&1 >> "%~dp0run.cmd"

schtasks /Delete /tn Band-Tracker-Schedule /f > NUL 2>&1
schtasks /Create /ru system /sc daily /tn Band-Tracker-Schedule /tr "%~dp0run.cmd" /ri 1 /du 24:00 /st 00:00

pause



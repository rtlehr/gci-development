@echo off
setlocal EnableExtensions
cd /d "%~dp0" || (
    echo ERROR: Could not open project directory.
    pause
    exit /b 1
)
echo.
echo ========================================
echo       Laravel / Vue Source Packager
echo ========================================
echo.
echo Project: %CD%
echo.
set "PACK_SCRIPT=%~dp0Pack-ProjectSource.ps1"
if not exist "%PACK_SCRIPT%" (
    echo ERROR: Missing Pack-ProjectSource.ps1 next to this BAT file.
    pause
    exit /b 1
)
set /p "ZIP_NAME=Enter ZIP name without .zip: "
if not defined ZIP_NAME (
    echo ERROR: A ZIP name is required.
    pause
    exit /b 1
)
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%PACK_SCRIPT%" -ProjectPath "%CD%" -ZipName "%ZIP_NAME%"
if errorlevel 1 (
    echo.
    echo ERROR: Packaging failed.
    pause
    exit /b 1
)
echo.
echo Packaging complete.
pause
endlocal

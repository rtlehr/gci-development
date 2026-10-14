@echo off
setlocal
cd /d "%~dp0"
echo.
echo Insite Portal - Azure Deployment Packager
echo =========================================
echo.
echo Source: %CD%
echo Production assets must have been built with npm run build.
echo This script never changes the source project or deploys to Azure.
echo.
set "ZIP_NAME=InsitePortal-Azure-Deployment"
set /p "CUSTOM_NAME=Enter ZIP name without .zip [InsitePortal-Azure-Deployment]: "
if not "%CUSTOM_NAME%"=="" set "ZIP_NAME=%CUSTOM_NAME%"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Pack-InsiteAzure.ps1" -ProjectPath "%CD%" -ZipName "%ZIP_NAME%"
if errorlevel 1 (
    echo.
    echo ERROR: Deployment package creation failed.
    pause
    exit /b 1
)
echo.
echo Deployment package ready. No Azure deployment has occurred.
pause

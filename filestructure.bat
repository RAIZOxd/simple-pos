@echo off
setlocal

:: Script to create the folder and file structure for the Simple POS System

:: Change directory to the location of this batch script to ensure
:: the project folder is created relative to the script's location.
cd /d "%~dp0"

:: Define the root project folder name
set PROJECT_FOLDER=simple-pos-project

echo Creating project root folder: %PROJECT_FOLDER%
:: Create the root project folder if it doesn't exist
if exist "%PROJECT_FOLDER%" (
    echo Folder "%PROJECT_FOLDER%" already exists. Files/Folders inside might be overwritten or added.
) else (
    md "%PROJECT_FOLDER%"
    if errorlevel 1 (
        echo Failed to create directory: %PROJECT_FOLDER%
        goto :eof
    )
    echo Created folder: %PROJECT_FOLDER%
)

:: Change into the project folder
cd "%PROJECT_FOLDER%"
echo.
echo Creating structure inside: %CD%
echo ========================================
echo.

:: Create top-level PHP files
echo [*] Creating top-level PHP files...
type nul > index.php
type nul > pos.php
type nul > products.php
type nul > sales_history.php
type nul > login.php
type nul > logout.php
echo     Done.
echo.

:: Create data directory and files
echo [*] Creating data directory and initial files...
md data
type nul > data\products.json
type nul > data\sales.json
type nul > data\config.json
type nul > data\users.json
:: Add a reminder note and basic .htaccess for Apache security
echo REMINDER: Ensure your web server (Apache/Nginx) is configured to DENY direct browser access to this '/data' directory! > data\readme_SECURITY_NOTE.txt
echo Deny from all > data\.htaccess
echo     Created data directory, JSON files, security note, and basic .htaccess.
echo.

:: Create modules directory and sub-structure
echo [*] Creating modules directory structure...
md modules
md modules\auth
type nul > modules\auth\auth_functions.php
md modules\products
type nul > modules\products\product_functions.php
md modules\sales
type nul > modules\sales\sale_functions.php
md modules\receipt
type nul > modules\receipt\receipt_template.php
md modules\utils
type nul > modules\utils\json_helpers.php
type nul > modules\utils\helpers.php
echo     Done.
echo.

:: Create templates directory and files
echo [*] Creating templates directory and files...
md templates
type nul > templates\header.php
type nul > templates\footer.php
type nul > templates\navigation.php
echo     Done.
echo.

:: Create assets directory and sub-structure
echo [*] Creating assets directory structure...
md assets
md assets\css
type nul > assets\css\custom.css
md assets\js
type nul > assets\js\main.js
echo     Done.
echo.

echo ========================================
echo Folder and file structure creation process complete within '%PROJECT_FOLDER%'.
echo ========================================
echo.

endlocal
pause
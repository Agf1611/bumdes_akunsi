@echo off
setlocal EnableExtensions EnableDelayedExpansion

color 0A
title Installer Localhost Windows - BUMDes

for %%I in ("%~dp0..\..") do set "SOURCE_DIR=%%~fI"
set "DEFAULT_XAMPP=C:\xampp"
set "DEFAULT_APP_FOLDER=bumdes-akuntansi"
set "DEFAULT_DB_NAME=bumdes_akuntansi"
set "DEFAULT_DB_USER=root"

cls
echo ============================================================
echo   INSTALLER WINDOWS LOCALHOST - SISTEM BUMDES
 echo ============================================================
echo.
echo Paket sumber:
echo %SOURCE_DIR%
echo.
echo Script ini akan:
echo 1. Menyalin aplikasi ke folder htdocs XAMPP
echo 2. Membuat folder kerja lokal yang dibutuhkan
echo 3. Mencoba membuat database kosong
echo 4. Membuka installer di browser
echo.

echo Pastikan Apache dan MySQL di XAMPP SUDAH dinyalakan.
echo.
set /p XAMPP_DIR=Lokasi folder XAMPP [%DEFAULT_XAMPP%]: 
if "%XAMPP_DIR%"=="" set "XAMPP_DIR=%DEFAULT_XAMPP%"

if not exist "%XAMPP_DIR%\htdocs" (
    echo.
    echo ERROR: Folder htdocs tidak ditemukan di:
    echo %XAMPP_DIR%
    echo.
    echo Install XAMPP dulu atau isi lokasi yang benar, lalu jalankan lagi.
    pause
    exit /b 1
)

set /p APP_FOLDER=Nama folder aplikasi di htdocs [%DEFAULT_APP_FOLDER%]: 
if "%APP_FOLDER%"=="" set "APP_FOLDER=%DEFAULT_APP_FOLDER%"

set "TARGET_DIR=%XAMPP_DIR%\htdocs\%APP_FOLDER%"

echo.
echo Target instalasi:
echo %TARGET_DIR%
echo.

if not exist "%TARGET_DIR%" mkdir "%TARGET_DIR%" >nul 2>nul

where robocopy >nul 2>nul
if errorlevel 1 (
    echo Menyalin file dengan xcopy...
    xcopy "%SOURCE_DIR%\*" "%TARGET_DIR%\" /E /I /Y >nul
    if errorlevel 1 (
        echo.
        echo ERROR: Gagal menyalin file ke folder target.
        pause
        exit /b 1
    )
) else (
    echo Menyalin file dengan robocopy...
    robocopy "%SOURCE_DIR%" "%TARGET_DIR%" /E /R:1 /W:1 /NFL /NDL /NJH /NJS /NP >nul
    set "RC=%ERRORLEVEL%"
    if !RC! GEQ 8 (
        echo.
        echo ERROR: Robocopy gagal. Kode: !RC!
        pause
        exit /b 1
    )
)

for %%D in (
    "app\config"
    "storage"
    "storage\logs"
    "storage\imports"
    "storage\backups"
    "storage\bank_reconciliations"
    "storage\journal_attachments"
    "public\uploads\profiles"
    "public\uploads\signatures"
) do (
    if not exist "%TARGET_DIR%\%%~D" mkdir "%TARGET_DIR%\%%~D" >nul 2>nul
)

if exist "%TARGET_DIR%\storage\installed.lock" del /f /q "%TARGET_DIR%\storage\installed.lock" >nul 2>nul
if exist "%TARGET_DIR%\app\config\generated.php" del /f /q "%TARGET_DIR%\app\config\generated.php" >nul 2>nul

echo.
echo Salin file selesai.
echo.

set /p DB_NAME=Nama database MySQL [%DEFAULT_DB_NAME%]: 
if "%DB_NAME%"=="" set "DB_NAME=%DEFAULT_DB_NAME%"
set /p DB_USER=User MySQL [%DEFAULT_DB_USER%]: 
if "%DB_USER%"=="" set "DB_USER=%DEFAULT_DB_USER%"
set /p DB_PASS=Password MySQL [kosongkan jika tidak ada]: 

set "MYSQL_EXE=%XAMPP_DIR%\mysql\bin\mysql.exe"
if exist "%MYSQL_EXE%" (
    echo.
    echo Mencoba membuat database %DB_NAME% ...
    if "%DB_PASS%"=="" (
        "%MYSQL_EXE%" -u"%DB_USER%" -e "CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 1>nul 2>nul
    ) else (
        "%MYSQL_EXE%" -u"%DB_USER%" -p"%DB_PASS%" -e "CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 1>nul 2>nul
    )

    if errorlevel 1 (
        echo WARNING: Database tidak berhasil dibuat otomatis.
        echo Anda tetap bisa lanjut. Nanti buat database kosong manual di phpMyAdmin.
    ) else (
        echo OK: Database kosong berhasil dipastikan ada.
    )
) else (
    echo WARNING: mysql.exe tidak ditemukan. Lewati pembuatan database otomatis.
)

echo.
set "INSTALL_URL=http://localhost/%APP_FOLDER%/install.php"
echo Installer siap dibuka di:
echo %INSTALL_URL%
echo.
start "" "%INSTALL_URL%"

echo Langkah berikutnya:
echo 1. Isi database host: 127.0.0.1
echo 2. Isi port: 3306
echo 3. Isi nama database: %DB_NAME%
echo 4. Isi user database: %DB_USER%
echo 5. Selesaikan form installer dan buat akun admin pertama
echo.
echo Jika browser tidak otomatis terbuka, copy URL di atas.
echo.
pause
exit /b 0

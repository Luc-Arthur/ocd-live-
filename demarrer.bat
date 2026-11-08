@echo off
REM OCD Live - installation + lancement + tunnel Cloudflare (Windows)
cd /d %~dp0

if not exist vendor\inertiajs (
    echo [1/4] Installation des dependances PHP...
    call composer install --no-interaction
)
if not exist .env (
    echo Creation du fichier .env et de la cle de l'application...
    copy .env.example .env >nul
    php artisan key:generate --force
)
if not exist database\database.sqlite (
    type nul > database\database.sqlite
)
if not exist node_modules\vue (
    echo [2/4] Installation des dependances front...
    call npm install
)
echo [3/4] Base de donnees + forfaits + donnees de demo...
php artisan migrate --force
php artisan db:seed --force
echo [4/4] Compilation du front...
call npm run build

echo Lancement du serveur sur http://localhost:8000 ...
start "OCD Live - serveur" php artisan serve --host=127.0.0.1 --port=8000
timeout /t 3 >nul

where cloudflared >nul 2>nul
if errorlevel 1 (
    echo.
    echo cloudflared n'est pas installe. Installe-le avec :
    echo    winget install --id Cloudflare.cloudflared
    echo puis relance ce fichier.
    pause
    exit /b
)
echo.
echo Ouverture du tunnel Cloudflare : le lien public https://....trycloudflare.com va s'afficher ci-dessous.
echo Laisse cette fenetre ouverte tant que la demo doit rester en ligne.
cloudflared tunnel --url http://localhost:8000

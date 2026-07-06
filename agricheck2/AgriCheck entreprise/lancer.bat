@echo off
setlocal

set "ROOT=%~dp0"
set "PY=%ROOT%.venv\Scripts\python.exe"
set "PORT=%~1"

if "%PORT%"=="" set "PORT=8080"

if not exist "%PY%" (
    echo Python du projet introuvable: %PY%
    echo Verifiez que le dossier .venv existe dans le projet.
    exit /b 1
)

set "DB_ENGINE=django.db.backends.sqlite3"
set "DB_NAME=%ROOT%db.sqlite3"

cd /d "%ROOT%"
echo Verification Django...
"%PY%" manage.py check || exit /b %ERRORLEVEL%

echo Migration SQLite locale...
"%PY%" manage.py migrate --noinput || exit /b %ERRORLEVEL%

echo.
echo Agricheck Entreprise demarre sur http://127.0.0.1:%PORT%/
echo Appuyez sur CTRL+C pour arreter le serveur.
echo.
"%PY%" manage.py runserver "127.0.0.1:%PORT%" --noreload

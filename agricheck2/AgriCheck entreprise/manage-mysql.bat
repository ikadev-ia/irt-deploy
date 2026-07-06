@echo off
setlocal

set "ROOT=%~dp0"
set "PY=%ROOT%.venv\Scripts\python.exe"

if not exist "%PY%" (
    echo Python du projet introuvable: %PY%
    echo Verifiez que le dossier .venv existe dans le projet.
    exit /b 1
)

cd /d "%ROOT%"
"%PY%" manage.py %*

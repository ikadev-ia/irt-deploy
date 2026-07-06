@echo off
cd /d "%~dp0"
set "DB_ENGINE=django.db.backends.sqlite3"
set "DB_NAME=%~dp0db.sqlite3"
if "%PYTHON_EXE%"=="" (
    if exist "%~dp0.venv\Scripts\python.exe" (
        set "PYTHON_EXE=%~dp0.venv\Scripts\python.exe"
    ) else if exist "C:\Users\kader diarra\PycharmProjects\AgriCheck entreprise\.venv\Scripts\python.exe" (
        set "PYTHON_EXE=C:\Users\kader diarra\PycharmProjects\AgriCheck entreprise\.venv\Scripts\python.exe"
    ) else (
        set "PYTHON_EXE=python"
    )
)
if not "%PYTHON_EXE%"=="python" if not exist "%PYTHON_EXE%" (
    echo Python introuvable: %PYTHON_EXE%
    echo Verifiez le chemin ou definissez PYTHON_EXE avant de relancer.
    pause
    exit /b 1
)

echo.
echo Agricheck Admin demarre...
echo URL ordinateur : http://127.0.0.1:8090/connexion/
echo URL telephone  : http://ADRESSE_IP_DU_PC:8090/connexion/
echo.
"%PYTHON_EXE%" manage.py migrate --noinput
if errorlevel 1 (
    echo Migration impossible.
    pause
    exit /b 1
)
"%PYTHON_EXE%" manage.py runserver 0.0.0.0:8090

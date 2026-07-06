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
"%PYTHON_EXE%" manage.py %*

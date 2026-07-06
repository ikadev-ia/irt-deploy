@echo off
setlocal
net session >nul 2>&1
if not "%errorlevel%"=="0" (
  echo Ce script doit etre lance en administrateur.
  echo Clic droit sur ce fichier, puis "Executer en tant qu administrateur".
  pause
  exit /b 1
)

netsh advfirewall firewall add rule name="Agricheck Local Backend 8000" dir=in action=allow protocol=TCP localport=8000
echo.
echo Port 8000 ouvert pour le backend local Agricheck.
pause

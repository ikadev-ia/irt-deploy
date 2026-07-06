@echo off
setlocal
cd /d "%~dp0.."
if exist "server_data\agricheck_api_keys.env" (
  for /f "usebackq tokens=1,* delims==" %%A in ("server_data\agricheck_api_keys.env") do (
    if not "%%A"=="" if not "%%B"=="" set "%%A=%%B"
  )
)
echo Demarrage du backend local Agricheck...
echo URL telephone sur le meme Wi-Fi: http://172.20.10.3:8000
echo URL emulateur Android: http://10.0.2.2:8000
echo URL PC: http://127.0.0.1:8000
echo.
"C:\src\flutter_windows_3.44.0-stable\flutter\bin\cache\dart-sdk\bin\dart.exe" run tools\agricheck_dev_server.dart
echo.
echo Le backend Agricheck s'est arrete.
pause

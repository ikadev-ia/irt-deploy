param(
    [int]$Port = 8000
)

$ErrorActionPreference = "Stop"
$Root = Resolve-Path -LiteralPath (Join-Path $PSScriptRoot "..")
$env:DB_ENGINE = "django.db.backends.sqlite3"
$env:DB_NAME = Join-Path $Root "db.sqlite3"

Set-Location -LiteralPath $Root
& (Join-Path $Root ".venv\Scripts\python.exe") manage.py migrate --noinput
& (Join-Path $Root ".venv\Scripts\python.exe") manage.py runserver "127.0.0.1:$Port" --noreload

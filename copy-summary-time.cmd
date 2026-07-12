@echo off
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "Set-Clipboard -Value (Get-Date -Format 'M.d-H:mm')"

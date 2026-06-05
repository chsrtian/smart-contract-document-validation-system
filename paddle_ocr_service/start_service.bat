@echo off
title PaddleOCR Service - Port 8001
echo ============================================================
echo  PaddleOCR Service
echo  Listening on: http://127.0.0.1:8001
echo  Health check: http://127.0.0.1:8001/health
echo  Keep this window OPEN while using the scanning module.
echo ============================================================
echo.

cd /d "%~dp0"

call venv\Scripts\activate.bat
uvicorn main:app --host 127.0.0.1 --port 8001
pause

@echo off
setlocal
cd /d "%~dp0"

echo ==========================================================
echo  Laboratorio de Teste Exaustivo de Login - FATEC-FV
echo ==========================================================

echo.
echo [1/2] Executando testes PHPUnit...
if exist "vendor\bin\phpunit.bat" (
    call vendor\bin\phpunit.bat --testdox
) else (
    echo PHPUnit nao encontrado em vendor\bin\phpunit.bat
)

echo.
echo [2/2] Executando testes BDD com Behave...
set "VENV_PYTHON=%CD%\.venv\Scripts\python.exe"

if not exist "%VENV_PYTHON%" (
    echo Ambiente .venv nao encontrado.
    echo Execute: python -m venv .venv ^& .venv\Scripts\pip install -r requirements.txt
    exit /b 1
)

"%VENV_PYTHON%" -m behave
exit /b %errorlevel%

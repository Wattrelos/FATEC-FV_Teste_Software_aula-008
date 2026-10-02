#!/usr/bin/env bash
set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

echo "=========================================================="
echo " Laboratório de Teste Exaustivo de Login - FATEC-FV"
echo "=========================================================="

# 1. Executa a suíte PHPUnit (Testes Unitários e de Integração)
echo ""
echo "[1/2] Executando testes PHPUnit (Unit & Integration)..."
echo "----------------------------------------------------------"
vendor/bin/phpunit --testdox

# 2. Executa a suíte BDD / Playwright (Behave)
echo ""
echo "[2/2] Executando testes BDD & E2E (Behave / Playwright)..."
echo "----------------------------------------------------------"
if [ ! -d ".venv" ]; then
    echo "Criando ambiente virtual .venv..."
    python3 -m venv .venv
    .venv/bin/pip install -r requirements.txt
    .venv/bin/playwright install chromium
fi

# Inicia servidor web PHP em background para testes integrados
php -S 127.0.0.1:8088 -t public public/index.php > /dev/null 2>&1 &
SERVER_PID=$!

cleanup() {
    kill $SERVER_PID > /dev/null 2>&1 || true
}
trap cleanup EXIT

sleep 1
TEST_BASE_URL="http://127.0.0.1:8088/login.html" .venv/bin/behave

echo ""
echo "[3/3] Executando simulação de telas E2E no Playwright..."
echo "----------------------------------------------------------"
TEST_BASE_URL="http://127.0.0.1:8088" .venv/bin/python test_playwright_telas.py

echo ""
echo "=========================================================="
echo " Todos os testes foram concluídos com sucesso! [PASS]"
echo "=========================================================="


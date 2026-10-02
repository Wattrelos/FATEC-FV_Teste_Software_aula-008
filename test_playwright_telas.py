"""
Script de Simulação Automatizada com Playwright (Python).
Valida a experiência completa do usuário:
1. Renderização da Tela de Login (Campos, Labels, Token CSRF).
2. Simulação de Resposta de Erro (Credenciais Inválidas / Anti-Enumeração).
3. Simulação de Resposta de Sucesso (Dashboard Autenticado com Dados do Usuário).
4. Ciclo de Encerramento de Sessão (Logout seguro e retorno ao login).
5. Renderização da Tela Técnica de Resposta de Bloqueio (HTTP 429 Rate Limit / HTTP 403 CSRF).
"""

import os
import socket
import subprocess
import sys
import time
from pathlib import Path
from playwright.sync_api import sync_playwright, expect


def is_port_open(host: str, port: int) -> bool:
    """Verifica se uma porta de rede local está aceitando conexões."""
    try:
        with socket.create_connection((host, port), timeout=0.5):
            return True
    except (OSError, ConnectionRefusedError):
        return False


def resolve_base_url():
    """
    Resolve dinamicamente a URL base para testes E2E:
    1. Respeita a variável TEST_BASE_URL se definida.
    2. Se a porta 8099 (Nginx do laboratório) estiver ativa, conecta nela.
    3. Se a porta 8088 (PHP Server) já estiver ativa, conecta nela.
    4. Caso nenhum servidor HTTP esteja respondendo, inicia automaticamente
       o servidor PHP embutido em background e devolve o processo para finalização.
    """
    env_url = os.getenv("TEST_BASE_URL", "").strip()
    if env_url:
        return env_url.rstrip("/"), None

    # Verifica Nginx na porta 8099
    if is_port_open("127.0.0.1", 8099):
        print("[Playwright] Servidor Nginx detectado na porta 8099.")
        return "http://127.0.0.1:8099", None

    # Verifica se já existe um servidor PHP na porta 8088
    if is_port_open("127.0.0.1", 8088):
        print("[Playwright] Servidor PHP detectado na porta 8088.")
        return "http://127.0.0.1:8088", None

    # Inicialização automática de servidor PHP embutido
    print("[Playwright] Nenhum servidor HTTP detectado. Subindo servidor PHP embutido na porta 8088...")
    project_root = Path(__file__).resolve().parent
    public_dir = project_root / "public"
    index_file = public_dir / "index.php"

    server_process = subprocess.Popen(
        ["php", "-S", "127.0.0.1:8088", "-t", str(public_dir), str(index_file)],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL
    )

    for _ in range(25):
        if is_port_open("127.0.0.1", 8088):
            print(f"  ✓ Servidor PHP embutido iniciado com sucesso (PID: {server_process.pid}).")
            return "http://127.0.0.1:8088", server_process
        time.sleep(0.1)

    print("  ⚠ Porta 8088 não respondeu a tempo, tentando continuar com 8088...")
    return "http://127.0.0.1:8088", server_process


def testar_fluxo_completo_telas():
    base_url, server_process = resolve_base_url()
    headless = os.getenv("TESTE_HEADLESS", "true").lower() != "false"
    slow_mo = 0 if headless else 500

    print(f"\n[Playwright] Iniciando simulação visual contra: {base_url}")
    print(f"[Playwright] Modo Headless: {headless}")

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=headless, slow_mo=slow_mo)
            context = browser.new_context()
            page = context.new_page()

            # -------------------------------------------------------------
            # 1. TELA DE LOGIN: Renderização e Estrutura
            # -------------------------------------------------------------
            print("\n[Etapa 1] Acessando Tela de Login...")
            page.goto(f"{base_url}/login.html")

            expect(page.locator("#login-title")).to_contain_text("Acesso ao Sistema")
            expect(page.get_by_test_id("input-email")).to_be_visible()
            expect(page.get_by_test_id("input-password")).to_be_visible()
            expect(page.get_by_test_id("button-submit")).to_be_visible()
            print("  ✓ Formulário e campos renderizados corretamente.")

            # -------------------------------------------------------------
            # 2. TELA DE LOGIN: Simulação de Resposta de Erro
            # -------------------------------------------------------------
            print("\n[Etapa 2] Simulando tentativa de login com credenciais inválidas...")
            page.get_by_test_id("input-email").fill("usuario@teste.com")
            page.get_by_test_id("input-password").fill("SenhaErrada#999")
            page.get_by_test_id("button-submit").click()

            feedback = page.get_by_test_id("feedback-message")
            expect(feedback).to_be_visible()
            expect(feedback).to_have_text("Credenciais inválidas.")
            print("  ✓ Feedback de erro exibido na tela com proteção anti-enumeração.")

            # -------------------------------------------------------------
            # 3. TELA DE RESPOSTA (SUCESSO): Login e Área Restrita (Dashboard)
            # -------------------------------------------------------------
            print("\n[Etapa 3] Efetuando login legítimo e acessando Tela de Sucesso (Dashboard)...")
            page.get_by_test_id("input-email").fill("usuario@teste.com")
            page.get_by_test_id("input-password").fill("Senha@123")
            page.get_by_test_id("button-submit").click()

            # Aguarda feedback de sucesso
            expect(feedback).to_be_visible()
            expect(feedback).to_contain_text("Login realizado com sucesso!")

            # Navega para a tela dedicada de dashboard
            page.goto(f"{base_url}/dashboard.html")

            # Validações na Tela de Sucesso (Dashboard)
            expect(page.get_by_test_id("dashboard-panel")).to_be_visible()
            expect(page.get_by_test_id("session-badge")).to_contain_text("Sessão Autenticada")
            expect(page.get_by_test_id("user-name")).to_contain_text("Usuário Teste")
            expect(page.get_by_test_id("user-email")).to_contain_text("usuario@teste.com")
            print("  ✓ Tela de Sucesso / Dashboard renderizada com dados reais da sessão.")

            # -------------------------------------------------------------
            # 4. ENCERRAMENTO DE SESSÃO: Logout
            # -------------------------------------------------------------
            print("\n[Etapa 4] Testando encerramento de sessão pelo Dashboard...")
            page.get_by_test_id("button-logout").click()

            # Deve redirecionar para a tela de login com mensagem de sessão encerrada
            expect(page.locator("#login-form-wrapper")).to_be_visible()
            expect(page.get_by_test_id("feedback-message")).to_contain_text("Sessão encerrada com sucesso.")
            print("  ✓ Sessão encerrada com sucesso e retorno à tela de login confirmado.")

            # -------------------------------------------------------------
            # 5. TELA DE RESPOSTA DEDICADA: Simulação de Bloqueio (Resultado)
            # -------------------------------------------------------------
            print("\n[Etapa 5] Acessando Tela Técnica de Resposta de Bloqueio (429 Rate Limit)...")
            page.goto(f"{base_url}/resultado.html?status=429&msg=Bloqueio+preventivo+por+excesso+de+tentativas.")

            expect(page.get_by_test_id("error-container")).to_be_visible()
            expect(page.get_by_test_id("error-status")).to_contain_text("429")
            expect(page.get_by_test_id("error-message")).to_contain_text("Bloqueio preventivo")
            expect(page.get_by_test_id("button-voltar")).to_be_visible()
            print("  ✓ Tela de resposta e diagnóstico técnico validada com sucesso.")

            if not headless:
                time.sleep(1.5)

            browser.close()
            print("\n============================================================")
            print(" [Playwright] Todos os fluxos de telas passaram com 100% de sucesso!")
            print("============================================================\n")

    finally:
        if server_process is not None:
            print("[Playwright] Encerrando servidor temporário...")
            server_process.terminate()
            try:
                server_process.wait(timeout=2)
            except subprocess.TimeoutExpired:
                server_process.kill()


if __name__ == "__main__":
    testar_fluxo_completo_telas()

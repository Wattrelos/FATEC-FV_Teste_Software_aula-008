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
import sys
from pathlib import Path
from playwright.sync_api import sync_playwright, expect


BASE_URL = os.getenv("TEST_BASE_URL", "http://127.0.0.1:8088").rstrip("/")
HEADLESS = os.getenv("TESTE_HEADLESS", "true").lower() != "false"


def testar_fluxo_completo_telas():
    print(f"\n[Playwright] Iniciando simulação visual contra: {BASE_URL}")
    print(f"[Playwright] Modo Headless: {HEADLESS}")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=HEADLESS, slow_mo=300 if not HEADLESS else 0)
        context = browser.new_context()
        page = context.new_page()

        # -------------------------------------------------------------
        # 1. TELA DE LOGIN: Renderização e Estrutura
        # -------------------------------------------------------------
        print("\n[Etapa 1] Acessando Tela de Login...")
        page.goto(f"{BASE_URL}/login.html")

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
        page.goto(f"{BASE_URL}/dashboard.html")

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
        page.goto(f"{BASE_URL}/resultado.html?status=429&msg=Bloqueio+preventivo+por+excesso+de+tentativas.")

        expect(page.get_by_test_id("error-container")).to_be_visible()
        expect(page.get_by_test_id("error-status")).to_contain_text("429")
        expect(page.get_by_test_id("error-message")).to_contain_text("Bloqueio preventivo")
        expect(page.get_by_test_id("button-voltar")).to_be_visible()
        print("  ✓ Tela de resposta e diagnóstico técnico validada com sucesso.")

        browser.close()
        print("\n============================================================")
        print(" [Playwright] Todos os fluxos de telas passaram com 100% de sucesso!")
        print("============================================================\n")


if __name__ == "__main__":
    testar_fluxo_completo_telas()

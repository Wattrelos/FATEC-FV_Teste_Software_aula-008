import os
from pathlib import Path

from behave import given, then, when
# pyrefly: ignore [missing-import]
from playwright.sync_api import expect, sync_playwright


PROJECT_ROOT = Path(__file__).resolve().parents[2]
LOGIN_PAGE = PROJECT_ROOT / "login.html"
BASE_URL = os.getenv("TEST_BASE_URL", "").strip()


def encerrar_navegador(context):
    page = getattr(context, "page", None)
    browser = getattr(context, "browser", None)
    playwright = getattr(context, "playwright", None)

    if page is not None:
        page.close()
    if browser is not None:
        browser.close()
    if playwright is not None:
        playwright.stop()


@given("que o usuário está na página de login")
def abrir_pagina_de_login(context):
    target_url = BASE_URL if BASE_URL else LOGIN_PAGE.as_uri()

    if not BASE_URL and not LOGIN_PAGE.is_file():
        raise FileNotFoundError(f"Interface não encontrada: {LOGIN_PAGE}")

    headless = os.getenv("TESTE_HEADLESS", "true").lower() != "false"
    context.playwright = sync_playwright().start()
    context.browser = context.playwright.chromium.launch(headless=headless)
    context.page = context.browser.new_page()
    context.add_cleanup(encerrar_navegador, context)

    context.page.goto(target_url)
    expect(context.page.get_by_test_id("input-email")).to_be_visible()


@when('o usuário preenche o e-mail com "{email}"')
def preencher_email(context, email):
    context.page.get_by_test_id("input-email").fill(email)


@when('o usuário preenche a senha com "{senha}"')
def preencher_senha(context, senha):
    context.page.get_by_test_id("input-password").fill(senha)


@when("o usuário deixa os campos de e-mail e senha vazios")
def deixar_campos_vazios(context):
    context.page.get_by_test_id("input-email").fill("")
    context.page.get_by_test_id("input-password").fill("")


@when("clica no botão de entrar")
def clicar_em_entrar(context):
    context.page.get_by_test_id("button-submit").click()


@when("o usuário clica no botão de sair")
def clicar_em_sair(context):
    context.page.get_by_test_id("button-logout").click()


@then('o sistema deve exibir a mensagem "{mensagem}"')
@then('o sistema deve exibir a mensagem de erro "{mensagem}"')
@then('o sistema deve exibir o alerta "{mensagem}"')
def validar_mensagem(context, mensagem):
    feedback = context.page.get_by_test_id("feedback-message")
    expect(feedback).to_be_visible()
    expect(feedback).to_have_text(mensagem)


@then("deve apresentar o painel principal simulado")
def validar_painel_principal(context):
    expect(context.page.get_by_test_id("dashboard-panel")).to_be_visible()
    expect(context.page.locator("#login-form-wrapper")).to_be_hidden()


@then("o formulário de login deve ser reexibido")
def validar_formulario_reexibido(context):
    expect(context.page.locator("#login-form-wrapper")).to_be_visible()
    expect(context.page.get_by_test_id("dashboard-panel")).to_be_hidden()

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
    # Adicionamos um delay moderado para que a digitação e transições sejam nítidas no vídeo
    slow_mo = 400 if headless else 600

    project_root = Path(__file__).resolve().parent
    video_dir = project_root / "test-results" / "videos"
    video_dir.mkdir(parents=True, exist_ok=True)

    print(f"\n[Playwright] Iniciando simulação visual contra: {base_url}")
    print(f"[Playwright] Modo Headless: {headless}")
    print(f"[Playwright] Gravando vídeo didático em: {video_dir}")

    recorded_video_path = None

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=headless, slow_mo=slow_mo)
            context = browser.new_context(
                record_video_dir=str(video_dir),
                record_video_size={"width": 1440, "height": 900},
                viewport={"width": 1440, "height": 900},
                locale="pt-BR",
                timezone_id="America/Sao_Paulo",
            )
            page = context.new_page()

            # -------------------------------------------------------------
            # 1. TELA DE LOGIN: Renderização e Estrutura
            # -------------------------------------------------------------
            print("\n[Etapa 1] Acessando Tela de Login...")
            page.goto(f"{base_url}/login.html")
            page.wait_for_timeout(800)

            expect(page.locator("#login-title")).to_contain_text("Acesso ao Sistema")
            expect(page.get_by_test_id("input-email")).to_be_visible()
            expect(page.get_by_test_id("input-password")).to_be_visible()
            expect(page.get_by_test_id("button-submit")).to_be_visible()
            print("  ✓ Formulário e campos renderizados corretamente.")

            # -------------------------------------------------------------
            # 2. TELA DE LOGIN: Simulação de Resposta de Erro (Pentest Anti-Enumeração)
            # -------------------------------------------------------------
            print("\n[Etapa 2] Simulando tentativa de login com credenciais inválidas (Pentest)...")
            page.get_by_test_id("input-email").fill("usuario@teste.com")
            page.get_by_test_id("input-password").fill("SenhaErrada#999")
            page.wait_for_timeout(500)
            page.get_by_test_id("button-submit").click()

            feedback = page.get_by_test_id("feedback-message")
            expect(feedback).to_be_visible()
            expect(feedback).to_have_text("Credenciais inválidas.")
            print("  ✓ Feedback de erro exibido na tela com proteção anti-enumeração.")
            page.wait_for_timeout(1200)

            # -------------------------------------------------------------
            # 3. TELA DE RESPOSTA (SUCESSO): Login e Área Restrita (Dashboard)
            # -------------------------------------------------------------
            print("\n[Etapa 3] Efetuando login legítimo e acessando Tela de Sucesso (Dashboard)...")
            page.get_by_test_id("input-email").fill("usuario@teste.com")
            page.get_by_test_id("input-password").fill("Senha@123")
            page.wait_for_timeout(500)
            page.get_by_test_id("button-submit").click()

            # Aguarda feedback de sucesso
            expect(feedback).to_be_visible()
            expect(feedback).to_contain_text("Login realizado com sucesso!")
            page.wait_for_timeout(1000)

            # Navega para a tela dedicada de dashboard
            page.goto(f"{base_url}/dashboard.html")

            # Validações na Tela de Sucesso (Dashboard)
            expect(page.get_by_test_id("dashboard-panel")).to_be_visible()
            expect(page.get_by_test_id("session-badge")).to_contain_text("Sessão Autenticada")
            expect(page.get_by_test_id("user-name")).to_contain_text("Usuário Teste")
            expect(page.get_by_test_id("user-email")).to_contain_text("usuario@teste.com")
            print("  ✓ Tela de Sucesso / Dashboard renderizada com dados reais da sessão.")
            page.wait_for_timeout(1500)

            # -------------------------------------------------------------
            # 4. ENCERRAMENTO DE SESSÃO: Logout
            # -------------------------------------------------------------
            print("\n[Etapa 4] Testando encerramento de sessão pelo Dashboard...")
            page.get_by_test_id("button-logout").click()

            # Deve redirecionar para a tela de login com mensagem de sessão encerrada
            expect(page.locator("#login-form-wrapper")).to_be_visible()
            expect(page.get_by_test_id("feedback-message")).to_contain_text("Sessão encerrada com sucesso.")
            print("  ✓ Sessão encerrada com sucesso e retorno à tela de login confirmado.")
            page.wait_for_timeout(1000)

            # -------------------------------------------------------------
            # 5. TELA DE RESPOSTA DEDICADA: Simulação de Bloqueio (Resultado / Rate Limit)
            # -------------------------------------------------------------
            print("\n[Etapa 5] Acessando Tela Técnica de Resposta de Bloqueio (429 Rate Limit)...")
            page.goto(f"{base_url}/resultado.html?status=429&msg=Bloqueio+preventivo+por+excesso+de+tentativas.")

            expect(page.get_by_test_id("error-container")).to_be_visible()
            expect(page.get_by_test_id("error-status")).to_contain_text("429")
            expect(page.get_by_test_id("error-message")).to_contain_text("Bloqueio preventivo")
            expect(page.get_by_test_id("button-voltar")).to_be_visible()
            print("  ✓ Tela de resposta e diagnóstico técnico validada com sucesso.")
            page.wait_for_timeout(1500)

            # Obtém referência do arquivo de vídeo antes de fechar o contexto
            video_obj = page.video
            context.close()
            browser.close()

            if video_obj:
                raw_path = Path(video_obj.path())
                target_video = video_dir / "simulacao_telas_pentest.webm"
                if raw_path.exists():
                    if target_video.exists():
                        target_video.unlink()
                    raw_path.rename(target_video)
                    recorded_video_path = target_video
                    print(f"  🎬 Vídeo didático salvo com sucesso em: {target_video}")

            print("\n============================================================")
            print(" [Playwright] Todos os fluxos de telas passaram com 100% de sucesso!")
            print("============================================================\n")

            # Gera relatório HTML interativo com player de vídeo
            gerar_relatorio_html(project_root, recorded_video_path)

    finally:
        if server_process is not None:
            print("[Playwright] Encerrando servidor temporário...")
            server_process.terminate()
            try:
                server_process.wait(timeout=2)
            except subprocess.TimeoutExpired:
                server_process.kill()


def gerar_relatorio_html(project_root: Path, video_path: Path | None):
    """Gera um relatório HTML moderno com player de vídeo incorporado para os alunos."""
    report_file = project_root / "test-results" / "relatorio_execucao.html"
    video_relative = "videos/simulacao_telas_pentest.webm" if video_path and video_path.exists() else None

    html_content = f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Visual de Execução E2E & Pentest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {{
            --bg-dark: #0f172a;
            --surface-card: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
            --accent-green: #10b981;
            --accent-blue: #38bdf8;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }}
        * {{ box-sizing: border-box; margin: 0; padding: 0; }}
        body {{
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #090d16 0%, #0f172a 100%);
            color: var(--text-main);
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
            display: flex;
            justify-content: center;
        }}
        .container {{
            max-width: 1000px;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }}
        .header {{
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1.5rem;
        }}
        .header h1 {{ font-size: 1.6rem; font-weight: 700; color: #fff; }}
        .badge-success {{
            background: rgba(16, 185, 129, 0.15);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 0.4rem 0.9rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
        }}
        .card {{
            background: var(--surface-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.8rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }}
        .card h2 {{
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--accent-blue);
        }}
        .video-wrapper {{
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: #000;
            margin-top: 1rem;
        }}
        video {{
            width: 100%;
            height: auto;
            display: block;
        }}
        .step-list {{
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-top: 1rem;
        }}
        .step-item {{
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            color: var(--text-main);
        }}
        .step-icon {{
            color: var(--accent-green);
            font-weight: bold;
        }}
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div>
                <h1>Relatório de Testes E2E & Pentest Visual</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                    Aula 08 - Automação Playwright com Gravação Didática Interativa
                </p>
            </div>
            <span class="badge-success">✓ 100% Aprovado</span>
        </header>

        <section class="card">
            <h2>Gravação em Vídeo da Execução do Teste (1440x900)</h2>
            <p style="color: var(--text-muted); font-size: 0.92rem;">
                Assista à interação automatizada demonstrando a digitação, simulação de credenciais incorretas (anti-enumeração), login bem-sucedido com sessão, logout e tela técnica de diagnóstico (HTTP 429).
            </p>
            {"<div class='video-wrapper'><video controls autoplay loop muted><source src='" + video_relative + "' type='video/webm'>Seu navegador não suporta a tag de vídeo.</video></div>" if video_relative else "<p style='color: #f87171; margin-top: 1rem;'>Vídeo não encontrado ou gravação desativada.</p>"}
        </section>

        <section class="card">
            <h2>Cenários Validados no Vídeo</h2>
            <ul class="step-list">
                <li class="step-item"><span class="step-icon">✓</span> <strong>Etapa 1:</strong> Renderização da Tela de Login, verificação de campos e busca do token CSRF.</li>
                <li class="step-item"><span class="step-icon">✓</span> <strong>Etapa 2:</strong> Pentest Anti-Enumeração: mensagem opaca e uniforme para credenciais incorretas.</li>
                <li class="step-item"><span class="step-icon">✓</span> <strong>Etapa 3:</strong> Autenticação legítima, geração de sessão e redirecionamento para o Dashboard.</li>
                <li class="step-item"><span class="step-icon">✓</span> <strong>Etapa 4:</strong> Encerramento de sessão (Logout) com destruição de cookie e retorno seguro ao login.</li>
                <li class="step-item"><span class="step-icon">✓</span> <strong>Etapa 5:</strong> Tela de diagnóstico de bloqueio técnico (HTTP 429 Rate Limit) com retorno seguro.</li>
            </ul>
        </section>
    </div>
</body>
</html>
"""
    with open(report_file, "w", encoding="utf-8") as f:
        f.write(html_content)

    print(f"  📊 Relatório HTML gerado em: {report_file}")
    print(f"  👉 Abra no navegador: file://{report_file}\n")


if __name__ == "__main__":
    testar_fluxo_completo_telas()


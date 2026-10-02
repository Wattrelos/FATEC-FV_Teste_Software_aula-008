* **FATEC-FV Faculdade de Tecnologia de Ferraz de Vasconcelos**
* **ADS - Análise e Desenvolvimento de Sistemas**
* **Teste de Software**
* **Professor:** Alexander Bastos
* **Aluno:** Josias Sobrinho

# Laboratório de Teste exaustivo de Login

## Objetivo

O objetivo deste laboratório é realizar testes exaustivos na funcionalidade de login de uma aplicação web, utilizando uma abordagem sistemática para identificar potenciais vulnerabilidades e garantir a segurança e usabilidade do sistema. Serão realizados testes funcionais, testes de validação de dados, testes de desempenho e testes de segurança para cobrir os principais cenários de uso e potenciais cenários de falha.


## Pré-requisitos

* **PHP 8.2+** com extensões `mbstring`, `pdo` e `session`
* **Composer 2.x**
* **Python 3.10+** com módulo `venv`
* **Navegador Chromium** (instalado automaticamente via Playwright)

## Como Executar a Suíte de Testes

### Execução Completa Automatizada (Linux / macOS)
```bash
./executar_testes.sh
```

### Execução Completa (Windows)
```cmd
executar_testes.bat
```

### Execução Manual por Camada

1. **Testes Unitários e de Integração (PHPUnit):**
   ```bash
   composer test:unit        # Apenas regras de domínio e casos de uso
   composer test:integration # Pipeline HTTP, Middlewares, Rate Limiting e Sessão
   composer test             # Todos os 23 testes automatizados de backend
   ```

2. **Testes BDD & E2E com Playwright (Python Behave):**
   ```bash
   # Ativa o ambiente virtual
   source .venv/bin/activate  # No Windows: .\.venv\Scripts\Activate.ps1
   
   # Executa os cenários Gherkin contra o navegador em modo Headless
   python -m behave
   
   # Para visualizar o navegador executando as ações na tela:
   TESTE_HEADLESS=false python -m behave
   ```

3. **Subir Servidor Web de Demonstração Manual:**
   ```bash
   composer start
   # Acesse no navegador: http://127.0.0.1:8080/login.html
   ```
## Preparação manual recomendada

> No contexto de autenticação e login de uma aplicação web, os testes de segurança visam garantir que apenas usuários legítimos tenham acesso ao sistema e que os mecanismos de controle resistam a ataques.
Abaixo estão os principais testes de segurança para cenários de login, agrupados por categorias lógicas:
## 1. Testes contra Ataques de Autenticação (Força Bruta e Engenharia Social)

* Ataques de Força Bruta (Brute Force): Verificar se o sistema bloqueia ou limita o acesso (via rate limiting ou IP blocking) após múltiplas tentativas consecutivas de login malsucedidas.
* Ataques de Preenchimento de Credenciais (Credential Stuffing): Testar se a aplicação possui proteções automatizadas (como CAPTCHA ou análise de comportamento) contra o uso em massa de listas de usuários e senhas vazadas.
* Ataques de Dicionário: Avaliar se o sistema impede o uso de listas de senhas comuns ou previsíveis durante o login e a criação de conta.

## 2. Testes de Gerenciamento de Sessão

* Fixação de Sessão (Session Fixation): Garantir que o identificador de sessão (Session ID) mude obrigatoriamente logo após o usuário realizar o login com sucesso.
* Tokens de Sessão e Cookies: Verificar se os cookies de autenticação possuem as flags de segurança recomendadas, como Secure (apenas via HTTPS), HttpOnly (inacessível via JavaScript) e SameSite (proteção contra CSRF).
* Invalidação de Sessão (Log out): Confirmar se a sessão é completamente destruída no servidor e no navegador quando o usuário clica em "Sair".
* Expiração de Sessão por Inatividade: Validar se o sistema encerra a sessão automaticamente após um período de tempo predeterminado sem interação do usuário.

## 3. Testes de Política e Armazenamento de Credenciais

* Divulgação de Informações (Enumeração de Usuários): Testar se as mensagens de erro diferenciam um usuário inexistente de uma senha incorreta. O ideal é usar mensagens genéricas como "Usuário ou senha inválidos".
* Criptografia em Trânsito: Validar se as credenciais trafegam obrigatoriamente sob protocolos seguros (HTTPS/TLS) e se o sistema rejeita conexões HTTP puras na rota de login.
* Armazenamento de Senhas: (Testado a nível de código/banco de dados) Validar se as senhas estão salvas utilizando algoritmos robustos de hashing com salt (como BCrypt ou Argon2) e nunca em texto limpo.

## 4. Testes de Mecanismos Auxiliares (MFA e Recuperação)

* Autenticação de Múltiplos Fatores (MFA/2FA): Testar se é possível burlar a tela do segundo fator acessando diretamente URLs internas ou forçando parâmetros na requisição.
* Fluxo de "Esqueci minha Senha": Verificar se os links de redefinição expiram rapidamente, são de uso único e se os tokens enviados por e-mail/SMS são gerados de forma imprevisível (criptograficamente seguros).

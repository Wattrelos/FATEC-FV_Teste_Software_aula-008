# 8. Tela de login, tela de sucesso e tela de diagnóstico de erro/bloqueio.

**Tela de login**, a **tela de sucesso (Dashboard do usuário)** e a **tela de diagnóstico de erro/bloqueio (Resultado)**, além de um script de teste E2E completo no **Playwright** que simula toda a experiência do usuário.

Todos os componentes já foram testados e executados com **100% de aprovação**.

---

### 1. Telas Desenvolvidas

As telas foram construídas com design moderno em *dark glassmorphism*, micro-animações, tipografia *Inter* e são totalmente integradas aos atributos `data-testid` para automação:

| Tela | Arquivo | Descrição e Recursos |
| :--- | :--- | :--- |
| **Login** | [login.html](/public/login.html) | Formulário com busca automática de token Anti-CSRF (`GET /api/v1/csrf-token`), validação instantânea, feedback inline acessível e modo híbrido (*live backend* ou offline). |
| **Sucesso / Dashboard** | [dashboard.html](/public/dashboard.html) | Tela exibida após autenticação bem-sucedida: exibe badge de sessão ativa (`session_regenerate_id`), nome do usuário (`#user-name`), e-mail (`#user-email`), status dos controles de segurança e botão de logout funcional (`#button-logout`). |
| **Resposta / Erro / Bloqueio** | [resultado.html](/public/resultado.html) | Tela técnica para simulações de resposta do servidor, apresentando código HTTP (ex: `401 Unauthorized`, `403 Forbidden`, `429 Too Many Requests`), explicação amigável, diagnóstico de mitigação OWASP e botão para retornar ao login. |

---

### 2. Automação e Simulação no Playwright

Criamos o script dedicado [test_playwright_telas.py](/test_playwright_telas.py) cobrindo **5 fluxos ponta a ponta**:

1. **Etapa 1:** Acessa [login.html](/public/login.html) e valida se os campos `#input-email`, `#input-password`, `#input-csrf-token` e `#button-submit` estão visíveis e funcionais.
2. **Etapa 2:** Digita credenciais incorretas e valida a exibição do alerta de erro com proteção anti-enumeração (*"Credenciais inválidas."*).
3. **Etapa 3:** Digita credenciais legítimas (`usuario@teste.com` / `Senha@123`) e valida o redirecionamento automático para [dashboard.html](/public/dashboard.html), conferindo os dados da sessão na tela.
4. **Etapa 4:** Aciona o botão de logout no Dashboard, valida a invalidação da sessão e o retorno para a tela de login com mensagem informativa.
5. **Etapa 5:** Simula o acesso à tela técnica [resultado.html](/public/resultado.html) simulando um bloqueio por *Rate Limiting* (HTTP 429).

---

### 3. Resultado da Pipeline de Testes

O script [executar_testes.sh](/executar_testes.sh) rodou as três camadas de validação com sucesso:

```text
[1/3] Executando PHPUnit (Testes Unitários e de Integração)...
OK (34 tests, 106 assertions)

[2/3] Executando Behave BDD (Cenários Gherkin)...
1 feature passed, 0 failed, 0 skipped
6 scenarios passed, 0 failed, 0 skipped
33 steps passed, 0 failed, 0 skipped

[3/3] Executando simulação de telas E2E no Playwright...
[Playwright] Iniciando simulação visual contra: http://127.0.0.1:8088
[Etapa 1] Acessando Tela de Login...                                              ✓ OK
[Etapa 2] Simulando tentativa de login com credenciais inválidas...              ✓ OK
[Etapa 3] Efetuando login legítimo e acessando Tela de Sucesso (Dashboard)...     ✓ OK
[Etapa 4] Testando encerramento de sessão pelo Dashboard...                       ✓ OK
[Etapa 5] Acessando Tela Técnica de Resposta de Bloqueio (429 Rate Limit)...     ✓ OK
============================================================
 [Playwright] Todos os fluxos de telas passaram com 100% de sucesso!
============================================================
```

---

### 4. Como Executar Você Mesmo

#### A. Ver o Playwright abrindo a janela do navegador em tempo real:
Abra o terminal na pasta do projeto e execute:
```bash
TESTE_HEADLESS=false ./.venv/bin/python test_playwright_telas.py
```
*(Você verá o Chromium abrir na sua tela, digitar os campos, navegar pelo Dashboard e testar o encerramento da sessão com pausas visuais de demonstração).*

#### B. Rodar toda a suíte de testes (PHPUnit + Behave + Playwright):
```bash
./executar_testes.sh
```

---

> [!WARNING]
> **Aviso Importante sobre o seu arquivo aberto [/etc/nginx/sites-available/teste-login](file:///etc/nginx/sites-available/teste-login):**
> No arquivo aberto no seu editor, entre as linhas 29 e 36, o bloco de processamento PHP foi substituído acidentalmente por um bloco duplicado de cache de arquivos estáticos.
> Para que o Nginx consiga repassar as requisições PHP para o backend via FPM na porta 8099, certifique-se de manter o bloco:
> ```nginx
>     # Processamento PHP via PHP-FPM 8.4
>     location ~ \.php$ {
>         include snippets/fastcgi-php.conf;
>         fastcgi_pass unix:/run/php/php8.4-fpm.sock;
>         fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
>         include fastcgi_params;
>     }
> ```
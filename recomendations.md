### 1. Roteiro de Ataque Agnóstico à Linguagem (Matriz de Teste)

Apresente o pentest dividido em **cenários de teste de segurança na camada HTTP** que qualquer backend precisa mitigar:

| Vetor de Ataque | Objetivo do Pentester (Aluno) | Como Testar (HTTP / Black-Box) | Critério de Sucesso do Pentest (Defesa Aprovada) |
| :--- | :--- | :--- | :--- |
| **Enumeração de Usuários** | Descobrir se um e-mail existe no banco | Enviar credencial inválida com e-mail existente vs. e-mail não existente | Ambas as requisições devem retornar a mesma mensagem opaca (`Credenciais inválidas`) e tempo de resposta homogêneo (*timing-attack proof*). |
| **Força Bruta / Dicionário** | Derrubar ou quebrar credenciais por volume | Disparar 10 a 50 requisições consecutivas de login para a mesma conta ou IP | O sistema deve aplicar *Rate Limiting* / *Throttling*, retornando status `429 Too Many Requests` ou bloqueio temporário. |
| **CSRF (*Cross-Site Request Forgery*)** | Forjar uma requisição de login/logout externa | Tentar submeter o formulário sem o token `X-CSRF-Token` ou com token adulterado/expirado | O backend deve rejeitar a requisição com `400 Bad Request` ou `403 Forbidden`. |
| **Fixação e Hijacking de Sessão** | Roubar ou reaproveitar identificador de sessão | Inspecionar os cookies `Set-Cookie` antes e depois do login e logout | O `Session ID` deve ser rotacionado (`session_regenerate_id`) após login e totalmente invalidado após logout, com flags `HttpOnly`, `SameSite=Strict/Lax` e `Secure`. |
| **Injeções (SQLi / NoSQLi)** | Burlar autenticação sem credenciais válidas | Injetar payloads clássicos nos campos de usuário e senha (ex: `' OR '1'='1 --` ou `admin' --`) | O sistema deve tratar como credencial inválida simples, sem vazar exceções, *stack traces* ou realizar login indevido. |
| **Validação de Inputs & Malformed Data** | Provocar falha de integridade ou crash | Enviar payloads com dados nulos, strings de 10.000 caracteres, ou JSONs corrompidos | Resposta tratada com validação formal (`422 Unprocessable Entity` ou `400 Bad Request`), sem *500 Internal Server Error*. |

---

### 2. Ferramentas Recomendadas para os Alunos

Recomende ferramentas que testem a aplicação a partir da camada de rede e navegador, mantendo o teste agnóstico:

1. **Ferramenta Interativa / Proxy de Interceptação (Padrão de Mercado):**
   - **OWASP ZAP (ZAP Proxy)** ou **Burp Suite Community**:
     - Permite que o aluno visualize a requisição crua saindo do [login.html](/login.html).
     - Executa o *Active Scan* e varredura de vulnerabilidades conhecidas (cabeçalhos de segurança ausentes, cookies inseguros, etc.).
2. **Automação via CLI / Scripts (Integrável em CI/CD):**
   - **cURL / Bash ou Python (`requests`):**
     - Criação de scripts simples que disparam laços de repetição simulando ataques de força bruta contra o endpoint `POST /api/v1/login`.
   - **Playwright / Cypress:**
     - Simulação de ataques a partir do navegador (ex.: tentar manipular o DOM para burlar campos obrigatórios ou injetar scripts XSS nos campos de erro).
3. **Auditoria de Cabeçalhos HTTP:**
   - Ferramentas como `curl -I` ou scanners para validar cabeçalhos defensivos: `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`.

---

### 3. Modelo de Roteiro/Entregável para os Trabalhos dos Alunos

Sugira aos alunos uma estrutura padrão de relatório para que documentem o pentest em seus respectivos sistemas:

```markdown
# Relatório de Teste de Intrusão (Pentest)

1. Identificação do Alvo (URL / Endpoint, Porta e Stack utilizada)
2. Metodologia (OWASP WSTG / Top 10)
3. Matriz de Vetores Avaliados:
   - [ ] Enumeração de Usuários
   - [ ] Ataque de Força Bruta e Avaliação de Rate Limiting
   - [ ] Validação de Token Anti-CSRF
   - [ ] Injeção de Código (SQLi, NoSQLi, XSS)
   - [ ] Ciclo de Vida e Segurança da Sessão (Cookies e Logout)
4. Evidências de Execução:
   - Print/Log da requisição e resposta crua HTTP (cURL / ZAP / Burp)
5. Veredito e Diagnóstico:
   - Se vulnerável: Qual a severidade (CVSS) e qual a correção recomendada.
   - Se seguro: Qual controle mitigatório garantiu a defesa.
```

---

### 4. Como Usar os Arquivos Desta Aula como Referência Prática

- **Como Alvo de Referência:** 
  A aplicação desta aula já possui os endpoints defensivos completos em [LoginAction.php](/backend/src/Http/Controllers/LoginAction.php), geração de token em [CsrfTokenAction.php](/backend/src/Http/Controllers/CsrfTokenAction.php) e tela de diagnóstico em [resultado.html](/resultado.html). Ela pode servir como o **"benchmark seguro"** que os alunos tentam atacar primeiro para aprender como uma aplicação deve responder.

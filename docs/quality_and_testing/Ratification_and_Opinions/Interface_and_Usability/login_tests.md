# Avaliação do especialista sobre testes de login

> Aqui está uma avaliação crítica, honesta e fundamentada nas diretrizes da **OWASP ASVS 4.0 (Authentication & Session Management)** e nos próprios objetivos do laboratório descritos no [README.md](/README.md).

---

### 1. Diagnóstico Geral: O que já está excelente?

Nossa suíte atual já cobre cerca de **85% a 90%** dos cenários críticos de autenticação de mercado:
* ✅ **Clean Architecture & Separação de Camadas:** Domínio imutável com Value Objects ([Email.php](/backend/src/Domain/Shared/ValueObjects/Email.php), [PasswordHash.php](/backend/src/Domain/Shared/ValueObjects/PasswordHash.php)).
* ✅ **Defesa em Profundidade contra Enumeração:** Mensagens genéricas idênticas (*"Credenciais inválidas."*) para senha errada e usuário inexistente.
* ✅ **Session Fixation:** Chamada obrigatória e testada de `session_regenerate_id(true)`.
* ✅ **Proteção Anti-CSRF:** Synchronizer Token Pattern exigido em requisições mutativas (POST) com código 403.
* ✅ **Rate Limiting:** Bloqueio HTTP 429 após 5 tentativas consecutivas com cabeçalhos `Retry-After` e `X-RateLimit-*`.
* ✅ **Higienização Defensiva:** Sanitização contra injeção de tags HTML/XSS, controle de caracteres nulos e bloqueio de Mass Assignment.
* ✅ **Logs Seguros:** Mascaramento automático e recursivo de senhas e PII com `***MASKED***`.

---

### 2. Avaliação Crítica: Onde estão os Pontos Cegos? (Críticas Negativas Construtivas)

Apesar de a suíte ser sólida, uma auditoria de segurança sênior apontaria **5 pontos cegos importantes**:

---

#### ⚠️ Ponto Cego 1: Ataques de Temporização (*Timing Attacks*) na Enumeração de Usuários
* **O Problema:** O backend retorna a mesma mensagem textual (*"Credenciais inválidas."*), **mas o tempo de resposta entrega o segredo**.
  * Se o e-mail **não existe**, o código lança a exceção quase que instantaneamente (**~0.2 milissegundos**).
  * Se o e-mail **existe**, o código executa `password_verify()` com BCrypt (custo 12), levando cerca de **~150 a 250 milissegundos**.
* **Impacto:** Um invasor pode automatizar um script simples medindo o tempo de resposta HTTP em milissegundos e descobrir com precisão quais e-mails estão cadastrados no sistema, **anulando a proteção anti-enumeração**.
* **Mitigação Recomendada:** Quando o usuário não existir no repositório, o caso de uso deve executar um cálculo de hash simulado (*dummy password_verify*) para que ambas as respostas consumam o mesmo tempo de CPU.

---

#### ⚠️ Ponto Cego 2: DoS por Comprimento Excessivo de Senha (*Password Length DoS — CWE-400*)
* **O Problema:** Nosso [PasswordHash.php](/backend/src/Domain/Shared/ValueObjects/PasswordHash.php) valida se a senha tem pelo menos 6 caracteres (`strlen < 6`), **mas não define um limite superior máximo**.
* **Impacto:** O algoritmo BCrypt trunca senhas internamente em 72 bytes, mas se um invasor enviar uma string de **500.000 caracteres (500 KB)** no campo `password`, a CPU do PHP será estrangulada calculando hashes pesados repetidamente, derrubando o servidor.
* **Mitigação Recomendada:** Estabelecer limite máximo de segurança (ex: máximo de 128 ou 256 caracteres no DTO e no Value Object) e criar teste automatizado para rejeitar payloads gigantes.

---

#### ⚠️ Ponto Cego 3: Expiração de Sessão por Inatividade (*Inactivity Timeout*)
* **O Problema:** O item 2.4 do seu próprio [README.md](/README.md) promete:
  > *"Expiração de Sessão por Inatividade: Validar se o sistema encerra a sessão automaticamente após um período de tempo predeterminado sem interação do usuário."*
* **A Realidade:** Nosso [CustomerAuthMiddleware.php](/backend/src/Http/Middlewares/CustomerAuthMiddleware.php) apenas verifica se `$_SESSION['customer_id']` existe. Ele não armazena nem valida um timestamp de `last_activity`. Se o cookie durar 30 dias, a sessão continuará aberta indefinidamente.
* **Mitigação Recomendada:** Registrar `$_SESSION['last_activity'] = time()` e invalidar a sessão (HTTP 401 / Redirecionamento 302) se a inatividade ultrapassar o limite (ex: 15 ou 30 minutos).

---

#### ⚠️ Ponto Cego 4: Força Bruta Distribuída (*Rate Limit apenas por IP*)
* **O Problema:** Nosso [RateLimitMiddleware.php](/backend/src/Http/Middlewares/RateLimitMiddleware.php) rastreia tentativas unicamente por `$clientIp`.
* **Impacto:** Se o atacante utilizar 5 proxies ou redes celulares diferentes (5 IPs), ele pode testar 4 senhas em cada IP (totalizando 20 tentativas contra a mesma conta `admin@teste.com`) **sem que nenhuma regra de Rate Limit seja disparada**.
* **Mitigação Recomendada:** Implementar ou documentar rate limiting híbrido (por IP **E** por identificador do usuário/e-mail de destino).

---

#### ⚠️ Ponto Cego 5: Replay de Token Anti-CSRF
* **O Problema:** O token CSRF gerado no endpoint `/api/csrf-token` permanece estático na sessão e pode ser reutilizado múltiplas vezes para tentativas consecutivas de login na mesma sessão de navegador.
* **Mitigação Recomendada:** Em formulários sensíveis de autenticação, o token pode ser rotacionado após cada submissão falha ou com sucesso.

---

### 3. Sugestão de Próximos Passos

Podemos implementar e adicionar testes automatizados imediatos para os **3 itens de maior valor técnico**:

1. **Teste de Proteção contra DoS de Senha:** Validar rejeição instantânea com código 400 de senhas maiores que 128/256 caracteres.
2. **Teste de Expiração de Sessão por Inatividade:** Validar no `CustomerAuthMiddlewareTest` o encerramento da sessão quando `time() - last_activity > 1800s`.
3. **Mitigação de Timing Attack:** Equalização de tempo no `AuthenticateCustomerUseCase` quando o e-mail não existir.

Quer que eu implemente esses 3 novos testes e as respectivas proteções no código?
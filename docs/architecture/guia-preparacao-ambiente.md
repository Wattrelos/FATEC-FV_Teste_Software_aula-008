

# Guia de Preparação e Manutenção do Ambiente de Testes

## **orquestrador profissional de ambiente de testes, diagnóstico e ciclo fechado de auto-cura (*Self-Healing*)**.

---

### 🛠️ O que foi melhorado e implementado

#### 1. Eliminação de Instalações Sequenciais Lentas (Idempotência)
* **Antes:** Executava `composer require` 10 vezes consecutivas, o que levava vários minutos a cada execução mesmo se tudo já estivesse instalado.
* **Agora:** O script verifica primeiro se os binários já existem em `vendor/bin`. Se já estiverem presentes, ele não reinstala nada à toa. Se precisar instalar ou se você passar `--reinstall`, ele roda um **único comando em lote** com a flag `-W` que garante a convivência perfeita de `squizlabs/php_codesniffer:^3.13` com `nunomaduro/phpinsights` no **PHP 8.4**.

#### 2. Criação dos Arquivos de Configuração do Ciclo Fechado
O script agora assegura a existência dos arquivos de configuração essenciais:
* [phpstan.neon](file:///var/www/html/teste-software/Aula-08/phpstan.neon): Análise estática no nível 5 para `backend/src`.
* [.php-cs-fixer.dist.php](file:///var/www/html/teste-software/Aula-08/.php-cs-fixer.dist.php): Regras PSR-12 com suporte explícito ao PHP 8.4 (`setUnsupportedPhpVersionAllowed(true)`), eliminando qualquer pergunta interativa no terminal.
* [rector.php](file:///var/www/html/teste-software/Aula-08/rector.php): Regras de modernização de código, *dead code* e qualidade para PHP 8.4.

#### 3. Automação do Ambiente E2E (Python Playwright + Behave)
* Cria automaticamente o `.venv` isolado, instala o `requirements.txt` e baixa os binários do Chromium com `playwright install chromium`.

#### 4. Novos Atalhos no [composer.json](file:///var/www/html/teste-software/Aula-08/composer.json)
Configuramos os scripts do Composer para facilitar o uso tanto por você quanto por agentes de IA:
* `composer self-healing` → Executa o ciclo fechado em sequência: **Formatação (PSR-12) ➔ Análise Estática (PHPStan) ➔ Testes (Pest)**.
* `composer test` → Roda os testes com Pest PHP.
* `composer stan` → Roda análise estática rigorosa de tipos.
* `composer fix` → Aplica correções automáticas de formatação.
* `composer rector` → Inspeciona modernizações de AST.
* `composer insights` → Avalia arquitetura e complexidade do código.

---

### 📊 Diagnóstico das Ferramentas Instaladas

Ao rodar `./install-test-tools.sh --check`, o script gera esta tabela em tempo real:

| Ferramenta | Categoria | Status | Versão Detectada |
| :--- | :--- | :---: | :--- |
| **Pest PHP** | Testes Modernos / TDD | 🟢 `[OK]` | Pest Testing Framework 3.8.7 |
| **PHPUnit** | Testes Unitários | 🟢 `[OK]` | PHPUnit 11.5.56 |
| **PHPStan** | Análise Estática | 🟢 `[OK]` | PHPStan 2.2.16 (Level 5 — 0 erros) |
| **Psalm** | Tipagem Estrita | 🟢 `[OK]` | Psalm 6.19.1 |
| **PHP-CS-Fixer** | Formatador PSR-12 | 🟢 `[OK]` | PHP CS Fixer 3.95.27 |
| **PHP_CodeSniffer** | Linter PSR-12 | 🟢 `[OK]` | PHP_CodeSniffer 3.13.6 |
| **Rector** | Refatoração AST | 🟢 `[OK]` | Rector 2.6.7 |
| **PHP Insights** | Qualidade & Métricas | 🟢 `[OK]` | PHP Insights v2.12.0 |
| **Mockery** | Dublês de Teste | 🟢 `[OK]` | v1.6.15 (*In-Code Library*) |
| **Playwright** | Automação Browser E2E | 🟢 `[OK]` | Version 1.63.0 (Chromium) |
| **Behave** | BDD / Gherkin | 🟢 `[OK]` | behave 1.3.3 |

---

### 🚀 Modos de Uso do Script

No seu terminal, você pode usar os seguintes comandos:

```bash
# 1. Modo Padrão: Valida o ambiente, instala o que faltar e exibe o resumo
./install-test-tools.sh

# 2. Modo Diagnóstico: Apenas exibe a tabela de status das ferramentas sem instalar nada
./install-test-tools.sh --check

# 3. Modo Auto-Cura: Executa diretamente o ciclo fechado (Fix -> Stan -> Pest)
./install-test-tools.sh --self-healing

# 4. Modo Reinstalação: Força reinstalação/atualização completa dos pacotes
./install-test-tools.sh --reinstall
```
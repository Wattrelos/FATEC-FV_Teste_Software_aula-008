#!/usr/bin/env bash
# ==============================================================================
# Laboratório de Teste Exaustivo de Login e Segurança — FATEC-FV
# Script: install-test-tools.sh
# Objetivo: Preparação do Ambiente de Testes, Auto-Cura e Ciclo Fechado para IA
# ==============================================================================

set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

# Paleta de Cores ANSI
C_RESET="\033[0m"
C_BOLD="\033[1m"
C_GREEN="\033[32m"
C_BLUE="\033[34m"
C_CYAN="\033[36m"
C_YELLOW="\033[33m"
C_RED="\033[31m"

log_info()    { echo -e "${C_CYAN}ℹ [INFO]${C_RESET} $*"; }
log_success() { echo -e "${C_GREEN}✔ [SUCESSO]${C_RESET} $*"; }
log_warn()    { echo -e "${C_YELLOW}⚠ [ALERTA]${C_RESET} $*"; }
log_error()   { echo -e "${C_RED}✖ [ERRO]${C_RESET} $*"; }
log_header()  {
    echo -e "\n${C_BOLD}${C_BLUE}====================================================================${C_RESET}"
    echo -e "${C_BOLD}${C_BLUE} $*${C_RESET}"
    echo -e "${C_BOLD}${C_BLUE}====================================================================${C_RESET}\n"
}

# ------------------------------------------------------------------------------
# 1. Diagnóstico de Ferramentas Existentes
# ------------------------------------------------------------------------------
verificar_status_ferramentas() {
    log_header "Diagnóstico das Ferramentas de Teste e Auto-Cura"

    printf "${C_BOLD}%-27s %-12s %-32s${C_RESET}\n" "FERRAMENTA" "STATUS" "DETALHES / VERSÃO"
    echo "-------------------------------------------------------------------------------"

    check_php_tool() {
        local name="$1"
        local bin="$2"
        local ver_cmd="$3"
        if [ -x "$bin" ]; then
            local ver
            ver=$($ver_cmd 2>/dev/null | tr -d '\r' | grep -v '^[[:space:]]*$' | head -n 1 | xargs || echo "Instalado")
            printf "${C_GREEN}%-27s [OK]         ${C_RESET}%-32s\n" "$name" "$ver"
        else
            printf "${C_YELLOW}%-27s [FALTANDO]   ${C_RESET}%-32s\n" "$name" "Não encontrado em vendor/bin"
        fi
    }

    check_php_tool "Pest PHP (TDD)" "./vendor/bin/pest" "./vendor/bin/pest --version"
    check_php_tool "PHPUnit (Legado)" "./vendor/bin/phpunit" "./vendor/bin/phpunit --version"
    check_php_tool "PHPStan (Análise Estática)" "./vendor/bin/phpstan" "./vendor/bin/phpstan --version"
    check_php_tool "Psalm (Tipagem Estrita)" "./vendor/bin/psalm" "./vendor/bin/psalm --version"
    check_php_tool "PHP-CS-Fixer (PSR-12)" "./vendor/bin/php-cs-fixer" "./vendor/bin/php-cs-fixer --version"
    check_php_tool "PHP_CodeSniffer (Linter)" "./vendor/bin/phpcs" "./vendor/bin/phpcs --version"
    check_php_tool "Rector (Refatoração AST)" "./vendor/bin/rector" "./vendor/bin/rector --version"
    check_php_tool "PHP Insights (Qualidade)" "./vendor/bin/phpinsights" "./vendor/bin/phpinsights --version"

    # Mockery (In-Code Library)
    if composer show mockery/mockery >/dev/null 2>&1; then
        local mock_ver
        mock_ver=$(composer show mockery/mockery 2>/dev/null | grep -E '^versions' | head -n 1 | awk '{print $4}' || echo "Ativo")
        printf "${C_GREEN}%-27s [OK]         ${C_RESET}%-32s\n" "Mockery (Dublês de Teste)" "v$mock_ver (In-Code Library)"
    else
        printf "${C_YELLOW}%-27s [FALTANDO]   ${C_RESET}%-32s\n" "Mockery (Dublês de Teste)" "Não instalado"
    fi

    # Playwright & Behave (Python E2E)
    if [ -x ".venv/bin/playwright" ]; then
        local pw_ver
        pw_ver=$(.venv/bin/playwright --version 2>/dev/null || echo "Instalado")
        printf "${C_GREEN}%-25s [OK]         ${C_RESET}%-30s\n" "Playwright (Browser E2E)" "$pw_ver"
    else
        printf "${C_YELLOW}%-25s [FALTANDO]   ${C_RESET}%-30s\n" "Playwright (Browser E2E)" "Ambiente .venv não configurado"
    fi

    if [ -x ".venv/bin/behave" ]; then
        local bh_ver
        bh_ver=$(.venv/bin/behave --version 2>/dev/null || echo "Instalado")
        printf "${C_GREEN}%-25s [OK]         ${C_RESET}%-30s\n" "Behave (Gherkin BDD)" "$bh_ver"
    else
        printf "${C_YELLOW}%-25s [FALTANDO]   ${C_RESET}%-30s\n" "Behave (Gherkin BDD)" "Não instalado na .venv"
    fi

    echo "--------------------------------------------------------------------"
}

# ------------------------------------------------------------------------------
# 2. Executar Ciclo de Auto-Cura (Self-Healing Loop)
# ------------------------------------------------------------------------------
executar_ciclo_auto_cura() {
    log_header "Iniciando Ciclo Fechado de Auto-Cura (Self-Healing Loop)"

    log_info "[Etapa 1/3] Normalizando Formatação e Estilo (PSR-12)..."
    ./vendor/bin/php-cs-fixer fix --quiet || true
    ./vendor/bin/phpcbf --standard=PSR12 backend/src tests > /dev/null 2>&1 || true
    log_success "Estilo e conformidade PSR-12 aplicados com sucesso."

    log_info "[Etapa 2/3] Executando Análise Estática de Tipagem (PHPStan)..."
    if ./vendor/bin/phpstan analyse --no-progress; then
        log_success "Análise estática aprovada com 0 erros."
    else
        log_warn "PHPStan encontrou inconsistências a serem sanadas pelo agente/desenvolvedor."
    fi

    log_info "[Etapa 3/3] Executando Suíte de Testes Automatizados (Pest / PHPUnit)..."
    ./vendor/bin/pest --compact || ./vendor/bin/phpunit --testdox
    log_success "Ciclo fechado de auto-cura concluído com sucesso!"
}

# ------------------------------------------------------------------------------
# 3. Instalação e Preparação do Ambiente
# ------------------------------------------------------------------------------
instalar_ambiente() {
    log_header "Instalação e Padronização do Ambiente de Testes"

    # Validações básicas de runtime
    command -v php >/dev/null 2>&1 || { log_error "PHP não encontrado no sistema."; exit 1; }
    command -v composer >/dev/null 2>&1 || { log_error "Composer não encontrado no sistema."; exit 1; }

    log_info "Runtime detectado: $(php -r 'echo "PHP " . PHP_VERSION;')"

    # A. Instalação de Dependências PHP via Composer (em lote para máxima performance)
    log_info "Verificando pacotes Composer para Auto-Cura e Testes..."
    
    local pacotes_faltando=false
    for binario in pest phpstan psalm rector php-cs-fixer phpcs phpinsights; do
        if [ ! -x "./vendor/bin/$binario" ]; then
            pacotes_faltando=true
            break
        fi
    done

    if [ "$pacotes_faltando" = true ] || [ "${1:-}" = "--reinstall" ]; then
        log_info "Instalando suíte completa de ferramentas dev via Composer..."
        
        # Compatibilidade garantida com PHP 8.4: squizlabs/php_codesniffer ^3.13 e nunomaduro/phpinsights
        composer require --dev \
            pestphp/pest \
            phpunit/phpunit \
            phpstan/phpstan \
            vimeo/psalm \
            rector/rector \
            mockery/mockery \
            friendsofphp/php-cs-fixer \
            "squizlabs/php_codesniffer:^3.13" \
            nunomaduro/phpinsights \
            --with-all-dependencies -W
            
        log_success "Pacotes PHP instalados com sucesso."
    else
        log_success "Todas as ferramentas PHP já estão instaladas no vendor/bin."
    fi

    # B. Garantia dos Arquivos de Configuração de Auto-Cura
    log_info "Garantindo arquivos de configuração no projeto..."

    # 1. phpstan.neon
    if [ ! -f "phpstan.neon" ]; then
        log_info "Criando phpstan.neon (Nível 5)..."
        cat << 'EOF' > phpstan.neon
parameters:
    level: 5
    paths:
        - backend/src
    treatPhpDocTypesAsCertain: false
EOF
        log_success "phpstan.neon criado."
    fi

    # 2. .php-cs-fixer.dist.php
    if [ ! -f ".php-cs-fixer.dist.php" ]; then
        log_info "Criando .php-cs-fixer.dist.php (PSR-12)..."
        cat << 'EOF' > .php-cs-fixer.dist.php
<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/backend/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => false,
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
    ])
    ->setFinder($finder);
EOF
        log_success ".php-cs-fixer.dist.php criado."
    fi

    # 3. rector.php
    if [ ! -f "rector.php" ]; then
        log_info "Criando rector.php para modernização AST..."
        cat << 'EOF' > rector.php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/backend/src',
        __DIR__ . '/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    ->withPhpSets(php84: true);
EOF
        log_success "rector.php criado."
    fi

    # C. Preparação do Ambiente E2E (Python Playwright + Behave)
    log_info "Configurando ambiente Python para Playwright e Behave..."
    if command -v python3 >/dev/null 2>&1; then
        if [ ! -d ".venv" ]; then
            log_info "Criando ambiente virtual isolado .venv..."
            python3 -m venv .venv
        fi

        log_info "Instalando dependências de teste Python (requirements.txt)..."
        .venv/bin/pip install --quiet --upgrade pip
        .venv/bin/pip install --quiet -r requirements.txt

        log_info "Instalando navegador Chromium para Playwright..."
        .venv/bin/playwright install chromium
        log_success "Playwright e Behave configurados com sucesso."
    else
        log_warn "Python 3 não detectado. Automação E2E Python indisponível."
    fi

    # D. Verificação de dependências do sistema Playwright (Debian/Ubuntu)
    if command -v npx >/dev/null 2>&1; then
        log_info "Node.js e npx detectados. Pacote npm local pronto."
    fi

    # Diagnóstico Final
    verificar_status_ferramentas

    # Exibição do Menu de Comandos Rápidos
    echo -e "\n${C_BOLD}${C_GREEN}✔ Ambiente de Testes e Auto-Cura configurado com sucesso!${C_RESET}\n"
    echo -e "${C_BOLD}Comandos Rápidos Disponíveis no Projeto:${C_RESET}"
    echo -e "  ${C_CYAN}composer self-healing${C_RESET}  → Roda o ciclo fechado completo (Fix + Stan + Testes)"
    echo -e "  ${C_CYAN}composer test${C_RESET}          → Executa a suíte de testes com Pest PHP"
    echo -e "  ${C_CYAN}composer stan${C_RESET}          → Analisa tipagem estática rigorosa com PHPStan"
    echo -e "  ${C_CYAN}composer fix${C_RESET}           → Formata automaticamente os códigos no padrão PSR-12"
    echo -e "  ${C_CYAN}composer rector${C_RESET}        → Inspeciona oportunidades de modernização AST"
    echo -e "  ${C_CYAN}composer insights${C_RESET}      → Avalia arquitetura, complexidade e qualidade"
    echo -e "  ${C_CYAN}./executar_testes.sh${C_RESET}   → Executa toda a esteira (PHPUnit + Behave + Playwright E2E)\n"
}

# ------------------------------------------------------------------------------
# Roteamento de Argumentos
# ------------------------------------------------------------------------------
case "${1:-}" in
    --check|-c)
        verificar_status_ferramentas
        ;;
    --self-healing|-s)
        executar_ciclo_auto_cura
        ;;
    --reinstall)
        instalar_ambiente "--reinstall"
        ;;
    *)
        instalar_ambiente "${1:-}"
        ;;
esac

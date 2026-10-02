<?php

declare(strict_types=1);

// Suporte para o servidor embutido do PHP (php -S) servir arquivos estáticos diretamente
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

// Configuração segura de cookies de sessão (OWASP ASVS v4)
\App\Infrastructure\Session\SessionConfig::applySecureCookieParams();

$app = App\AppBootstrap::create();

$app->run();


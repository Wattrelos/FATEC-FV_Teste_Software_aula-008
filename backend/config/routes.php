<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardAction;
use App\Http\Controllers\LoginAction;
use App\Http\Controllers\LogoutAction;
use App\Http\Middlewares\CustomerAuthMiddleware;
use App\Http\Middlewares\RateLimitMiddleware;
use Slim\App;

return static function (App $app): void {
    // Rotas de Autenticação com proteção contra Brute Force
    $app->post('/api/login', LoginAction::class)->add(RateLimitMiddleware::class);
    $app->post('/login', LoginAction::class)->add(RateLimitMiddleware::class);

    // Rotas de Encerramento de Sessão
    $app->post('/api/logout', LogoutAction::class);
    $app->get('/logout', LogoutAction::class);

    // Rota Raiz
    $app->get('/', function ($request, $response) {
        return $response->withHeader('Location', '/login.html')->withStatus(302);
    });

    // Rotas Protegidas (Exigem Sessão Ativa)
    $app->get('/api/dashboard', DashboardAction::class)->add(CustomerAuthMiddleware::class);
    $app->get('/api/me', DashboardAction::class)->add(CustomerAuthMiddleware::class);
};


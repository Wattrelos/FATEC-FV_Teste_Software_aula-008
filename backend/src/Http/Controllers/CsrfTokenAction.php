<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middlewares\CsrfMiddleware;
use App\Http\Responders\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Endpoint para fornecimento do token anti-CSRF atual da sessão.
 */
final class CsrfTokenAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (empty($_SESSION[CsrfMiddleware::SESSION_KEY])) {
            $_SESSION[CsrfMiddleware::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        $token = (string) $_SESSION[CsrfMiddleware::SESSION_KEY];

        return JsonResponder::success($response, [
            'csrf_token' => $token,
        ]);
    }
}

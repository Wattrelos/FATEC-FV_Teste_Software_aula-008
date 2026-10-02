<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Responders\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controller/Action para encerramento completo e seguro da sessão (Logout).
 */
final class LogoutAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Limpa todas as variáveis da sessão
        $_SESSION = [];

        // Invalida o cookie de sessão no cliente
        $sessionName = session_name();
        $cookieParams = session_get_cookie_params();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $accept = $request->getHeaderLine('Accept');
        $isJson = str_contains($accept, 'application/json')
            || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

        $cookieHeader = sprintf(
            '%s=deleted; expires=%s; Max-Age=0; path=%s; domain=%s; httponly',
            $sessionName,
            gmdate('D, d-M-Y H:i:s T', 1),
            $cookieParams['path'] ?? '/',
            $cookieParams['domain'] ?? ''
        );

        if (!$isJson) {
            return $response
                ->withHeader('Set-Cookie', $cookieHeader)
                ->withHeader('Location', '/login.html')
                ->withStatus(302);
        }

        return JsonResponder::success(
            $response->withHeader('Set-Cookie', $cookieHeader),
            ['message' => 'Sessão encerrada com sucesso.'],
            200
        );
    }
}

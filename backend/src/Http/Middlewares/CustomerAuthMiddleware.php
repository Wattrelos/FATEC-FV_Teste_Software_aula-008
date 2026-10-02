<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Http\Responders\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Middleware para proteção de rotas restritas via verificação de sessão ativa.
 */
final class CustomerAuthMiddleware implements MiddlewareInterface
{
    public const DEFAULT_MAX_IDLE_SECONDS = 1800; // 30 minutos

    public function __construct(
        private readonly int $maxIdleSeconds = self::DEFAULT_MAX_IDLE_SECONDS
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $customerId = $_SESSION['customer_id'] ?? null;
        $now = time();
        $isExpired = false;

        if ($customerId !== null) {
            $lastActivity = $_SESSION['last_activity'] ?? null;
            if ($lastActivity !== null && ($now - (int) $lastActivity) > $this->maxIdleSeconds) {
                $isExpired = true;
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_unset();
                }
                $_SESSION = [];
                $customerId = null;
            } else {
                $_SESSION['last_activity'] = $now;
            }
        }

        if (!$customerId) {
            $accept = $request->getHeaderLine('Accept');
            $isJson = str_contains($accept, 'application/json')
                || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

            $errorMessage = $isExpired
                ? 'Sessão expirada por inatividade. Faça login novamente.'
                : 'Acesso não autorizado. Sessão inválida ou expirada.';

            if ($isJson) {
                $res = new SlimResponse();

                return JsonResponder::error($res, $errorMessage, 401);
            }

            // Redirecionamento para o formulário de login
            $redirectUrl = $isExpired ? '/login.html?error=' . urlencode($errorMessage) : '/login.html';
            $res = new SlimResponse();

            return $res
                ->withHeader('Location', $redirectUrl)
                ->withStatus(302);
        }

        return $handler->handle($request);
    }
}

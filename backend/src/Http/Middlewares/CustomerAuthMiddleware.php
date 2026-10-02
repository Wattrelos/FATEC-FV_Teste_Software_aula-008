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
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $customerId = $_SESSION['customer_id'] ?? null;

        if (!$customerId) {
            $accept = $request->getHeaderLine('Accept');
            $isJson = str_contains($accept, 'application/json') 
                || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

            if ($isJson) {
                $res = new SlimResponse();
                return JsonResponder::error($res, 'Acesso não autorizado. Sessão inválida ou expirada.', 401);
            }

            // Redirecionamento para o formulário de login
            $res = new SlimResponse();
            return $res
                ->withHeader('Location', '/login.html')
                ->withStatus(302);
        }

        return $handler->handle($request);
    }
}

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
 * Middleware para proteção contra Cross-Site Request Forgery (Anti-CSRF).
 *
 * Implementa o Synchronizer Token Pattern:
 * - Gera um token criptograficamente seguro e armazena na sessão do usuário.
 * - Valida requisições mutativas (POST, PUT, DELETE, PATCH) contra o token de sessão.
 * - Suporta leitura do token via cabeçalho HTTP (X-CSRF-Token) ou corpo (csrf_token).
 * - Rejeita tokens ausentes ou divergentes com HTTP 403 Forbidden.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public const SESSION_KEY = 'csrf_token';
    public const HEADER_NAME = 'X-CSRF-Token';
    public const BODY_KEY = 'csrf_token';

    public function process(Request $request, RequestHandler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Garante que haja um token anti-CSRF na sessão
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        $sessionToken = (string) $_SESSION[self::SESSION_KEY];
        $method = strtoupper($request->getMethod());

        // Para métodos mutativos, a validação é mandatória
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $submittedToken = $this->extractToken($request);

            if ($submittedToken === null || !hash_equals($sessionToken, $submittedToken)) {
                $res = new SlimResponse();

                return JsonResponder::error(
                    $res,
                    'Acesso negado: Token anti-CSRF inválido ou ausente.',
                    403
                );
            }

            // Rotação pós-validação (Single-Use Token Pattern / OWASP Anti-Replay):
            // Regenera o token da sessão após o consumo para mitigar ataques de repetição
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
            $sessionToken = (string) $_SESSION[self::SESSION_KEY];
        }

        $response = $handler->handle($request);

        // Anexa o token de volta nos cabeçalhos da resposta para clientes SPA/fetch
        return $response->withHeader(self::HEADER_NAME, $sessionToken);
    }

    private function extractToken(Request $request): ?string
    {
        // 1. Tenta extrair do cabeçalho X-CSRF-Token
        $headerToken = $request->getHeaderLine(self::HEADER_NAME);
        if (!empty($headerToken)) {
            return trim($headerToken);
        }

        // 2. Tenta extrair do corpo da requisição (parsed body ou json)
        $body = $request->getParsedBody();
        if (is_array($body) && !empty($body[self::BODY_KEY])) {
            return trim((string) $body[self::BODY_KEY]);
        }

        // 3. Tenta extrair de JSON cru rebobinando a stream PSR-7
        $bodyStream = $request->getBody();
        $raw = (string) $bodyStream;
        if ($bodyStream->isSeekable()) {
            $bodyStream->rewind();
        }

        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json) && !empty($json[self::BODY_KEY])) {
                return trim((string) $json[self::BODY_KEY]);
            }
        }

        return null;
    }
}

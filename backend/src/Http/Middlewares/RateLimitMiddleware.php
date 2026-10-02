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
 * Middleware para mitigação de Ataques de Força Bruta (Brute Force) e Credential Stuffing.
 * Bloqueia requisições que excedam o limite estipulado por IP ou identificador de cliente.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    /** @var array<string, array{count: int, reset_time: int}> */
    private static array $storage = [];

    public function __construct(
        private readonly int $maxAttempts = 5,
        private readonly int $decaySeconds = 60
    ) {
    }

    public static function reset(): void
    {
        self::$storage = [];
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Aplica o rate limiting apenas na rota de submissão de login (POST)
        if ($request->getMethod() !== 'POST') {
            return $handler->handle($request);
        }

        $serverParams = $request->getServerParams();
        $clientIp = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
        $now = time();

        if (isset(self::$storage[$clientIp])) {
            $data = self::$storage[$clientIp];

            if ($now < $data['reset_time']) {
                if ($data['count'] >= $this->maxAttempts) {
                    $retryAfter = $data['reset_time'] - $now;
                    $res = (new SlimResponse())
                        ->withHeader('Retry-After', (string) $retryAfter)
                        ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
                        ->withHeader('X-RateLimit-Remaining', '0');

                    return JsonResponder::error(
                        $res,
                        "Muitas tentativas consecutivas. Tente novamente em {$retryAfter} segundos.",
                        429
                    );
                }
                self::$storage[$clientIp]['count']++;
            } else {
                // Janela expirada, reinicia
                self::$storage[$clientIp] = [
                    'count' => 1,
                    'reset_time' => $now + $this->decaySeconds,
                ];
            }
        } else {
            self::$storage[$clientIp] = [
                'count' => 1,
                'reset_time' => $now + $this->decaySeconds,
            ];
        }

        $response = $handler->handle($request);

        $remaining = max(0, $this->maxAttempts - self::$storage[$clientIp]['count']);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
    }
}

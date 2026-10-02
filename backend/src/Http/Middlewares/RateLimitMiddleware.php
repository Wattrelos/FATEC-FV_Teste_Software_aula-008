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

        $body = (array) $request->getParsedBody();
        if ($body === []) {
            $raw = (string) $request->getBody();
            if ($raw !== '') {
                $body = (array) (json_decode($raw, true) ?? []);
                $request->getBody()->rewind();
            }
        }

        $targetEmail = isset($body['email']) && is_string($body['email']) && trim($body['email']) !== ''
            ? strtolower(trim($body['email']))
            : null;

        $ipKey = 'ip:' . $clientIp;
        $accKey = $targetEmail !== null ? 'acc:' . $targetEmail : null;

        $ipRetry = $this->getRetryAfterIfBlocked($ipKey, $now);
        $accRetry = $accKey !== null ? $this->getRetryAfterIfBlocked($accKey, $now) : null;

        $retryAfter = $ipRetry ?? $accRetry;

        if ($retryAfter !== null) {
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

        $this->incrementAttempt($ipKey, $now);
        if ($accKey !== null) {
            $this->incrementAttempt($accKey, $now);
        }

        $response = $handler->handle($request);

        $ipCount = self::$storage[$ipKey]['count'] ?? 1;
        $accCount = $accKey !== null ? (self::$storage[$accKey]['count'] ?? 1) : 0;
        $currentMax = max($ipCount, $accCount);
        $remaining = max(0, $this->maxAttempts - $currentMax);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
    }

    private function getRetryAfterIfBlocked(string $key, int $now): ?int
    {
        if (isset(self::$storage[$key])) {
            $data = self::$storage[$key];
            if ($now < $data['reset_time'] && $data['count'] >= $this->maxAttempts) {
                return $data['reset_time'] - $now;
            }
        }

        return null;
    }

    private function incrementAttempt(string $key, int $now): void
    {
        if (isset(self::$storage[$key])) {
            if ($now < self::$storage[$key]['reset_time']) {
                self::$storage[$key]['count']++;

                return;
            }
        }

        self::$storage[$key] = [
            'count' => 1,
            'reset_time' => $now + $this->decaySeconds,
        ];
    }
}

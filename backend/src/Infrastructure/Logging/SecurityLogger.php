<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

/**
 * Logger de Segurança com mascaramento obrigatório de dados sensíveis (PII / Credenciais).
 * Garante conformidade com OWASP e LGPD ao impedir que senhas e segredos vazem em arquivos de log.
 */
final class SecurityLogger
{
    private const SENSITIVE_KEYS = [
        'password',
        'plainpassword',
        'senha',
        'secret',
        'token',
        'authorization',
        'authtoken',
        'auth_token',
        'csrftoken',
        'csrf_token',
        'apikey',
        'api_key',
    ];


    /** @var list<array{level: string, message: string, context: array<string, mixed>, timestamp: int}> */
    private array $logs = [];

    public function log(string $level, string $message, array $context = []): void
    {
        $sanitizedContext = $this->maskSensitiveData($context);
        $sanitizedMessage = $this->maskSensitiveText($message);

        $this->logs[] = [
            'level' => strtoupper($level),
            'message' => $sanitizedMessage,
            'context' => $sanitizedContext,
            'timestamp' => time(),
        ];
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Mascara recursivamente propriedades sensíveis em arrays de contexto.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function maskSensitiveData(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower(str_replace(['_', '-'], '', (string) $key));

            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                $result[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $result[$key] = $this->maskSensitiveData($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Remove senhas literais caso apareçam em mensagens de exceção em texto puro.
     */
    public function maskSensitiveText(string $text): string
    {
        return (string) preg_replace(
            '/(password|senha|secret|token)\s*[:=]\s*["\']?([^"\'\s&]+)["\']?/i',
            '$1=***MASKED***',
            $text
        );
    }

    /**
     * @return list<array{level: string, message: string, context: array<string, mixed>, timestamp: int}>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    public function clear(): void
    {
        $this->logs = [];
    }
}

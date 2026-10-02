<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

/**
 * Utilitário centralizado de configuração de sessão e cookies segundo OWASP ASVS v4.
 *
 * Garante que cookies de autenticação possuam as flags de segurança recomendadas:
 * - HttpOnly: true (inacessível via JavaScript / mitiga roubo por XSS)
 * - SameSite: Lax (protege contra CSRF cross-origin)
 * - Secure: true (obrigatório quando em tráfego HTTPS)
 * - Path: '/'
 * - Lifetime: 3600 (tempo de vida restrito)
 */
final class SessionConfig
{
    public const DEFAULT_LIFETIME = 3600;
    public const DEFAULT_SAMESITE = 'Lax';
    public const DEFAULT_PATH = '/';

    /**
     * Retorna o array de parâmetros recomendado para session_set_cookie_params.
     *
     * @return array{lifetime: int, path: string, domain: string, secure: bool, httponly: bool, samesite: string}
     */
    public static function getRecommendedCookieParams(?bool $isHttps = null): array
    {
        $secure = $isHttps ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');

        return [
            'lifetime' => self::DEFAULT_LIFETIME,
            'path' => self::DEFAULT_PATH,
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => self::DEFAULT_SAMESITE,
        ];
    }

    /**
     * Aplica os parâmetros seguros caso a sessão ainda não tenha sido iniciada.
     */
    public static function applySecureCookieParams(?bool $isHttps = null): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_set_cookie_params(self::getRecommendedCookieParams($isHttps));
        }
    }
}

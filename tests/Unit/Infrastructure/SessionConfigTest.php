<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Session\SessionConfig;
use PHPUnit\Framework\TestCase;

final class SessionConfigTest extends TestCase
{
    public function testDeveRetornarParametrosDeCookieComFlagsDeSegurancaRecomendadasPelaOWASP(): void
    {
        $params = SessionConfig::getRecommendedCookieParams(isHttps: false);

        $this->assertEquals(3600, $params['lifetime']);
        $this->assertEquals('/', $params['path']);
        $this->assertEquals('', $params['domain']);
        $this->assertTrue($params['httponly'], 'Flag HttpOnly deve ser obrigatoriamente true para impedir acesso via JS');
        $this->assertEquals('Lax', $params['samesite'], 'SameSite deve ser Lax ou Strict para mitigar CSRF');
        $this->assertFalse($params['secure'], 'Secure deve ser false em conexões HTTP locais');
    }

    public function testDeveAtivarFlagSecureQuandoHttpsEstiverHabilitado(): void
    {
        $params = SessionConfig::getRecommendedCookieParams(isHttps: true);

        $this->assertTrue($params['secure'], 'Flag Secure deve ser true quando sob protocolo HTTPS');
        $this->assertTrue($params['httponly']);
        $this->assertEquals('Lax', $params['samesite']);
    }

    public function testDeveInferirFlagSecureAutomaticamenteApartirDeVariaveisDeServidor(): void
    {
        // Simula HTTPS ativo nas variáveis globais do servidor
        $_SERVER['HTTPS'] = 'on';
        $paramsHttps = SessionConfig::getRecommendedCookieParams();
        $this->assertTrue($paramsHttps['secure']);

        // Simula HTTP puro
        unset($_SERVER['HTTPS']);
        $paramsHttp = SessionConfig::getRecommendedCookieParams();
        $this->assertFalse($paramsHttp['secure']);
    }
}

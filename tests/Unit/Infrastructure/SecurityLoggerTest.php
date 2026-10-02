<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Logging\SecurityLogger;
use PHPUnit\Framework\TestCase;

final class SecurityLoggerTest extends TestCase
{
    private SecurityLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new SecurityLogger();
    }

    public function testDeveMascararSenhasEChavesSensiveisIniciaisEmContexto(): void
    {
        $contexto = [
            'email' => 'usuario@teste.com',
            'password' => 'SenhaUltraSecreta@123',
            'plainPassword' => 'MinhaSenhaReal',
            'token' => 'jwt.token.secreto',
            'api_key' => 'sk_live_123456789',
            'origem_ip' => '192.168.1.1',
        ];

        $mascarado = $this->logger->maskSensitiveData($contexto);

        $this->assertEquals('usuario@teste.com', $mascarado['email']);
        $this->assertEquals('192.168.1.1', $mascarado['origem_ip']);
        $this->assertEquals('***MASKED***', $mascarado['password']);
        $this->assertEquals('***MASKED***', $mascarado['plainPassword']);
        $this->assertEquals('***MASKED***', $mascarado['token']);
        $this->assertEquals('***MASKED***', $mascarado['api_key']);
    }

    public function testDeveMascararSenhasEmEstruturasAninhadas(): void
    {
        $contexto = [
            'requisicao' => [
                'credenciais' => [
                    'senha' => 'SegredoNivel2',
                ],
                'usuario' => 'admin',
            ],
        ];

        $mascarado = $this->logger->maskSensitiveData($contexto);

        $this->assertEquals('***MASKED***', $mascarado['requisicao']['credenciais']['senha']);
        $this->assertEquals('admin', $mascarado['requisicao']['usuario']);
    }

    public function testDeveMascararSenhasLiteraisEmMensagensDeErro(): void
    {
        $mensagemCrua = 'Erro ao processar login: password="SuperSenha@123" para o e-mail teste@dominio.com';
        $mascarada = $this->logger->maskSensitiveText($mensagemCrua);

        $this->assertStringNotContainsString('SuperSenha@123', $mascarada);
        $this->assertStringContainsString('password=***MASKED***', $mascarada);
    }

    public function testDeveGarantirQueNenhumLogGravadoContemSenhaEmTextoPuro(): void
    {
        $senhaSecreta = 'SenhaPrivada@2026';

        $this->logger->warning('Falha de tentativa de login', [
            'email' => 'usuario@teste.com',
            'password' => $senhaSecreta,
        ]);

        $logs = $this->logger->getLogs();
        $this->assertCount(1, $logs);

        $jsonLogs = (string) json_encode($logs);

        // A senha em texto puro NUNCA deve aparecer no payload final dos logs
        $this->assertStringNotContainsString($senhaSecreta, $jsonLogs);
        $this->assertStringContainsString('***MASKED***', $jsonLogs);
    }
}

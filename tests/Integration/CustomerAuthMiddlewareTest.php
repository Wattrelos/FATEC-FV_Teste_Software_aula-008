<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\AppBootstrap;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

final class CustomerAuthMiddlewareTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        $this->app = AppBootstrap::create();
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION = [];
    }

    public function testDeveBloquearAcessoNaoAutorizadoComStatus401ParaJson(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/dashboard')
            ->withHeader('Accept', 'application/json');

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Acesso não autorizado', $data['error']);
    }

    public function testDeveRedirecionarParaLoginComStatus302ParaNavegador(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/dashboard')
            ->withHeader('Accept', 'text/html');

        $response = $this->app->handle($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login.html', $response->getHeaderLine('Location'));
    }

    public function testDevePermitirAcessoQuandoSessaoEstiverAutenticada(): void
    {
        $_SESSION['customer_id'] = 1;
        $_SESSION['customer_name'] = 'Usuário Teste';
        $_SESSION['customer_email'] = 'usuario@teste.com';

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/dashboard')
            ->withHeader('Accept', 'application/json');

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Usuário Teste', $data['data']['user']['name']);
    }

    public function testDeveExpirarSessaoAposPeriodoDeInatividade(): void
    {
        $_SESSION['customer_id'] = 1;
        $_SESSION['customer_name'] = 'Usuário Inativo';
        $_SESSION['customer_email'] = 'inativo@teste.com';
        // Simula última atividade há mais de 1800 segundos (30 minutos)
        $_SESSION['last_activity'] = time() - 1801;

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/dashboard')
            ->withHeader('Accept', 'application/json');

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        // Deve retornar 401 informando expiração por inatividade
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Sessão expirada por inatividade', $data['error']);

        // A sessão deve ter sido limpa e invalidada
        $this->assertArrayNotHasKey('customer_id', $_SESSION);
    }

    public function testDeveManterSessaoAtivaSeDentroDoTempoLimite(): void
    {
        $_SESSION['customer_id'] = 1;
        $_SESSION['customer_name'] = 'Usuário Ativo';
        $_SESSION['customer_email'] = 'ativo@teste.com';
        $tempoAnterior = time() - 30; // 30 segundos atrás
        $_SESSION['last_activity'] = $tempoAnterior;

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/dashboard')
            ->withHeader('Accept', 'application/json');

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);

        // Deve ter atualizado last_activity para o momento atual
        $this->assertGreaterThanOrEqual($tempoAnterior, $_SESSION['last_activity']);
    }
}

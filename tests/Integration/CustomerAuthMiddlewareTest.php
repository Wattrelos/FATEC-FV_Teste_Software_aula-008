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
}

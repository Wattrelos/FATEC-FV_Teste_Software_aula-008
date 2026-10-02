<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\AppBootstrap;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

final class LoginActionTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        $this->app = AppBootstrap::create();
    }

    public function testDeveRetornarStatus200EPayloadJsonQuandoLoginForValido(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/login')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        $request->getBody()->write((string) json_encode([
            'email'    => 'usuario@teste.com',
            'password' => 'Senha@123',
        ]));

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Login realizado com sucesso!', $data['data']['message']);
        $this->assertEquals('usuario@teste.com', $data['data']['user']['email']);
    }

    public function testDeveRetornarStatus401QuandoCredenciaisForemInvalidas(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/login')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        $request->getBody()->write((string) json_encode([
            'email'    => 'usuario@teste.com',
            'password' => 'SenhaErrada',
        ]));

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Credenciais inválidas.', $data['error']);
    }

    public function testDeveRetornarStatus400QuandoCamposObrigatoriosForemVazios(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/login')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        $request->getBody()->write((string) json_encode([
            'email'    => '',
            'password' => '',
        ]));

        $response = $this->app->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testDeveRetornarRedirecionamento302ParaSubmissaoHTMLFormulario(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/login')
            ->withParsedBody([
                'email'    => 'usuario@teste.com',
                'password' => 'Senha@123',
            ]);

        $response = $this->app->handle($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/dashboard.html', $response->getHeaderLine('Location'));
    }
}

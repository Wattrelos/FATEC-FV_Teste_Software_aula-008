<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\AppBootstrap;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

final class LogoutActionTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        $this->app = AppBootstrap::create();
    }

    public function testDeveEncerrarSessaoERetornarStatus200ParaRequisicaoJson(): void
    {
        $_SESSION['customer_id'] = 1;

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/logout')
            ->withHeader('Accept', 'application/json');

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEmpty($_SESSION);
        $this->assertTrue($response->hasHeader('Set-Cookie'));
        $this->assertStringContainsString('deleted', $response->getHeaderLine('Set-Cookie'));
    }

    public function testDeveRedirecionarParaLoginAposLogoutViaNavegador(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/logout')
            ->withHeader('Accept', 'text/html');

        $response = $this->app->handle($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login.html', $response->getHeaderLine('Location'));
    }
}

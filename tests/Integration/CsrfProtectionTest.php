<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\AppBootstrap;
use App\Http\Middlewares\CsrfMiddleware;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

final class CsrfProtectionTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        $this->app = AppBootstrap::create();
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION = [];
        \App\Http\Middlewares\RateLimitMiddleware::reset();
    }


    public function testDeveBloquearRequisicaoPostSemTokenCsrfComStatus403(): void
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

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Token anti-CSRF inválido ou ausente', $data['error']);
    }

    public function testDeveBloquearRequisicaoPostComTokenCsrfAdulteradoOuInvalido(): void
    {
        $_SESSION[CsrfMiddleware::SESSION_KEY] = 'token_verdadeiro_secreto_12345';

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/login')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-CSRF-Token', 'token_malicioso_forjado');

        $request->getBody()->write((string) json_encode([
            'email'    => 'usuario@teste.com',
            'password' => 'Senha@123',
        ]));

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Token anti-CSRF', $data['error']);
    }

    public function testDevePermitirRequisicaoPostComTokenCsrfValido(): void
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION[CsrfMiddleware::SESSION_KEY] = $token;

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/login')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-CSRF-Token', $token);

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
    }

    public function testDeveFornecerTokenCsrfValidoViaEndpoint(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/csrf-token')
            ->withHeader('Accept', 'application/json');

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['data']['csrf_token']);
        $this->assertEquals(64, strlen($data['data']['csrf_token'])); // 32 bytes hex = 64 chars
        $this->assertEquals($_SESSION[CsrfMiddleware::SESSION_KEY], $data['data']['csrf_token']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\AppBootstrap;
use App\Http\Middlewares\CsrfMiddleware;
use App\Http\Middlewares\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

final class RateLimitTest extends TestCase
{
    private App $app;
    private string $csrfToken;

    protected function setUp(): void
    {
        $this->app = AppBootstrap::create();
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $this->csrfToken = bin2hex(random_bytes(32));
        $_SESSION[CsrfMiddleware::SESSION_KEY] = $this->csrfToken;
        RateLimitMiddleware::reset();
    }


    public function testDeveBloquearAtaqueDeForcaBrutaAposLimiteDeTentativas(): void
    {
        $serverRequestFactory = new ServerRequestFactory();

        // Realiza 5 tentativas de login consecutivas (limite permitido)
        for ($i = 1; $i <= 5; $i++) {
            $activeToken = (string) $_SESSION[CsrfMiddleware::SESSION_KEY];
            $request = $serverRequestFactory
                ->createServerRequest('POST', '/api/login', ['REMOTE_ADDR' => '192.168.1.100'])
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Accept', 'application/json')
                ->withHeader('X-CSRF-Token', $activeToken);

            $request->getBody()->write((string) json_encode([
                'email' => 'usuario@teste.com',
                'password' => 'SenhaIncorreta' . $i,
            ]));

            $response = $this->app->handle($request);
            $this->assertEquals(401, $response->getStatusCode(), "Tentativa {$i} deveria retornar 401");
        }

        // A 6ª tentativa deve ser bloqueada com HTTP 429 Too Many Requests
        $activeToken = (string) $_SESSION[CsrfMiddleware::SESSION_KEY];
        $bloqueioRequest = $serverRequestFactory
            ->createServerRequest('POST', '/api/login', ['REMOTE_ADDR' => '192.168.1.100'])
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-CSRF-Token', $activeToken);


        $bloqueioRequest->getBody()->write((string) json_encode([
            'email' => 'usuario@teste.com',
            'password' => 'OutraTentativa',
        ]));

        $bloqueioResponse = $this->app->handle($bloqueioRequest);
        $body = (string) $bloqueioResponse->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(429, $bloqueioResponse->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Muitas tentativas consecutivas', $data['error']);
        $this->assertTrue($bloqueioResponse->hasHeader('Retry-After'));
    }

    public function testDeveBloquearAtaqueDeForcaBrutaDistribuidoMesmoComIpsDiferentesContraMesmaConta(): void
    {
        $serverRequestFactory = new ServerRequestFactory();

        // Simula 5 atacantes em IPs diferentes (botnet/proxies) tentando a mesma conta-alvo
        for ($i = 1; $i <= 5; $i++) {
            $fakeIp = '10.0.0.' . $i;
            $activeToken = (string) $_SESSION[CsrfMiddleware::SESSION_KEY];
            $request = $serverRequestFactory
                ->createServerRequest('POST', '/api/login', ['REMOTE_ADDR' => $fakeIp])
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Accept', 'application/json')
                ->withHeader('X-CSRF-Token', $activeToken);

            $request->getBody()->write((string) json_encode([
                'email' => 'vitima.distribuida@teste.com',
                'password' => 'TentativaSenha' . $i,
            ]));

            $response = $this->app->handle($request);
            $this->assertEquals(401, $response->getStatusCode(), "IP {$fakeIp} deveria retornar 401 na tentativa {$i}");
        }

        // A 6ª tentativa, vinda de um 6º IP inédito ('10.0.0.6'), deve ser bloqueada com 429 devido ao limite da conta
        $activeToken = (string) $_SESSION[CsrfMiddleware::SESSION_KEY];
        $bloqueioDistribuido = $serverRequestFactory
            ->createServerRequest('POST', '/api/login', ['REMOTE_ADDR' => '10.0.0.6'])
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-CSRF-Token', $activeToken);

        $bloqueioDistribuido->getBody()->write((string) json_encode([
            'email' => 'vitima.distribuida@teste.com',
            'password' => 'OutraSenhaBot',
        ]));

        $responseDistribuida = $this->app->handle($bloqueioDistribuido);
        $body = (string) $responseDistribuida->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(429, $responseDistribuida->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Muitas tentativas consecutivas', $data['error']);
        $this->assertTrue($responseDistribuida->hasHeader('Retry-After'));
    }
}

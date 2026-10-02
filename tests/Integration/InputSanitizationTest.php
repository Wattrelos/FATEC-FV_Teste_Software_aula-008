<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\AppBootstrap;
use App\Http\Middlewares\CsrfMiddleware;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

final class InputSanitizationTest extends TestCase
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
        \App\Http\Middlewares\RateLimitMiddleware::reset();
    }


    public function testDeveRejeitarTentativaDeInjecaoXssNoEmail(): void
    {
        $xssPayload = "<script>alert('XSS')</script>";

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/login')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-CSRF-Token', $this->csrfToken);

        $request->getBody()->write((string) json_encode([
            'email' => $xssPayload,
            'password' => 'Senha@123',
        ]));

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();

        // O payload XSS não pode ser refletido sem escape no corpo da resposta
        $this->assertStringNotContainsString("<script>", $body);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testDeveNeutralizarInjecaoSqlNasCredenciais(): void
    {
        $sqlInjection = "' OR '1'='1' --";

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/login')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-CSRF-Token', $this->csrfToken);

        $request->getBody()->write((string) json_encode([
            'email' => 'usuario@teste.com',
            'password' => $sqlInjection,
        ]));

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        // Deve recusar com a mensagem genérica padrão sem erro SQL
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Credenciais inválidas.', $data['error']);
    }

    public function testDeveBloquearPropriedadesExtrasParaEvitarMassAssignment(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/login')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-CSRF-Token', $this->csrfToken);

        // Payload com injeção de parâmetros adicionais maliciosos
        $request->getBody()->write((string) json_encode([
            'email' => 'usuario@teste.com',
            'password' => 'Senha@123',
            'is_admin' => true,
            'role' => 'superuser',
            'credit_limit' => 999999.00,
            'status' => false,
        ]));

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);

        // Garante que nenhum dos parâmetros maliciosos vazou para a sessão
        $this->assertArrayNotHasKey('is_admin', $_SESSION);
        $this->assertArrayNotHasKey('role', $_SESSION);
        $this->assertArrayNotHasKey('credit_limit', $_SESSION);

        // Garante que o retorno JSON contém apenas os dados autorizados do usuário
        $this->assertArrayNotHasKey('is_admin', $data['data']['user']);
        $this->assertArrayNotHasKey('role', $data['data']['user']);
    }

    public function testDeveRejeitarSenhaExcessivamenteLongaParaPrevenirDoS(): void
    {
        $hugePassword = str_repeat('A', 5000);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/login')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-CSRF-Token', $this->csrfToken);

        $request->getBody()->write((string) json_encode([
            'email' => 'usuario@teste.com',
            'password' => $hugePassword,
        ]));

        $response = $this->app->handle($request);
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        // Deve retornar 400 Bad Request informando limite de tamanho
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('tamanho máximo permitido', $data['error']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\AppBootstrap;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SecurityHeadersTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        $this->app = AppBootstrap::create();
    }

    public function testDeveRetornarHeadersDeSegurancaObrigatorios(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/');

        $response = $this->app->handle($request);

        $this->assertEquals('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertEquals('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $response->getHeaderLine('X-XSS-Protection'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        $this->assertStringContainsString('Content-Security-Policy', implode(',', array_keys($response->getHeaders())));
    }
}

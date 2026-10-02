<?php

declare(strict_types=1);

use App\Application\Customer\UseCases\AuthenticateCustomerUseCase;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Http\Controllers\CsrfTokenAction;
use App\Http\Controllers\DashboardAction;
use App\Http\Controllers\LoginAction;
use App\Http\Controllers\LogoutAction;
use App\Http\Middlewares\CsrfMiddleware;
use App\Http\Middlewares\CustomerAuthMiddleware;
use App\Http\Middlewares\RateLimitMiddleware;
use App\Http\Middlewares\SecurityHeadersMiddleware;
use App\Infrastructure\Logging\SecurityLogger;
use App\Infrastructure\Persistence\InMemoryCustomerRepository;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;


return static function (ContainerBuilder $containerBuilder): void {
    $containerBuilder->addDefinitions([
        // Repositório em memória compartilhado (Singleton)
        CustomerRepositoryInterface::class => static function (): CustomerRepositoryInterface {
            static $repo = null;
            if ($repo === null) {
                $repo = new InMemoryCustomerRepository();
            }
            return $repo;
        },

        // Casos de Uso
        AuthenticateCustomerUseCase::class => static function (ContainerInterface $c): AuthenticateCustomerUseCase {
            return new AuthenticateCustomerUseCase(
                $c->get(CustomerRepositoryInterface::class)
            );
        },

        // Logger de Segurança com mascaramento de dados sensíveis
        SecurityLogger::class => static function (): SecurityLogger {
            static $logger = null;
            if ($logger === null) {
                $logger = new SecurityLogger();
            }
            return $logger;
        },

        // Controllers / Actions
        LoginAction::class => static function (ContainerInterface $c): LoginAction {
            return new LoginAction(
                $c->get(AuthenticateCustomerUseCase::class),
                $c->get(SecurityLogger::class)
            );
        },

        LogoutAction::class => static function (): LogoutAction {
            return new LogoutAction();
        },

        DashboardAction::class => static function (): DashboardAction {
            return new DashboardAction();
        },

        CsrfTokenAction::class => static function (): CsrfTokenAction {
            return new CsrfTokenAction();
        },

        // Middlewares
        CsrfMiddleware::class => static function (): CsrfMiddleware {
            return new CsrfMiddleware();
        },

        RateLimitMiddleware::class => static function (): RateLimitMiddleware {
            return new RateLimitMiddleware(maxAttempts: 5, decaySeconds: 60);
        },

        CustomerAuthMiddleware::class => static function (): CustomerAuthMiddleware {
            return new CustomerAuthMiddleware();
        },

        SecurityHeadersMiddleware::class => static function (): SecurityHeadersMiddleware {
            return new SecurityHeadersMiddleware();
        },
    ]);
};


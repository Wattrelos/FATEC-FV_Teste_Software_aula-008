<?php

declare(strict_types=1);

use App\Application\Customer\UseCases\AuthenticateCustomerUseCase;
use App\Domain\Customer\Repositories\CustomerRepositoryInterface;
use App\Http\Controllers\DashboardAction;
use App\Http\Controllers\LoginAction;
use App\Http\Controllers\LogoutAction;
use App\Http\Middlewares\CustomerAuthMiddleware;
use App\Http\Middlewares\RateLimitMiddleware;
use App\Http\Middlewares\SecurityHeadersMiddleware;
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

        // Controllers / Actions
        LoginAction::class => static function (ContainerInterface $c): LoginAction {
            return new LoginAction(
                $c->get(AuthenticateCustomerUseCase::class)
            );
        },

        LogoutAction::class => static function (): LogoutAction {
            return new LogoutAction();
        },

        DashboardAction::class => static function (): DashboardAction {
            return new DashboardAction();
        },

        // Middlewares
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

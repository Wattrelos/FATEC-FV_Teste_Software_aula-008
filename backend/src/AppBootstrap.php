<?php

declare(strict_types=1);

namespace App;

use App\Http\Middlewares\SecurityHeadersMiddleware;
use DI\ContainerBuilder;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;

final class AppBootstrap
{
    public static function create(): App
    {
        $containerBuilder = new ContainerBuilder();
        $dependencies = require __DIR__ . '/../config/dependencies.php';
        $dependencies($containerBuilder);

        $container = $containerBuilder->build();
        SlimAppFactory::setContainer($container);

        $app = SlimAppFactory::create();
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->add(SecurityHeadersMiddleware::class);

        $routes = require __DIR__ . '/../config/routes.php';
        $routes($app);

        return $app;
    }
}

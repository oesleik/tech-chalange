<?php

declare(strict_types=1);

use App\Core\BaseController;
use App\Core\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

$app = \DI\Bridge\Slim\Bridge::create($container);

$app->addRoutingMiddleware();

$app->addErrorMiddleware(
    displayErrorDetails: true,   // false em produção
    logErrors: true,
    logErrorDetails: true
);

$app->get('/', [BaseController::class, "index"]);
$app->get('/health', [BaseController::class, "health"]);

$app->run();

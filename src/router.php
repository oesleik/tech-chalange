<?php

declare(strict_types=1);

use App\Core\BaseController;
use App\Core\Config\AppConfig;
use App\Core\ServiceContainerBuilder;

$containerBuilder = new ServiceContainerBuilder();
$container = $containerBuilder->build();

$appConfig = new AppConfig();
$app = \DI\Bridge\Slim\Bridge::create($container);

$app->addRoutingMiddleware();

$app->addErrorMiddleware(
    displayErrorDetails: !$appConfig->getAmbiente()->isProd(),
    logErrors: true,
    logErrorDetails: true
);

$app->get('/', [BaseController::class, "index"]);
$app->get('/health', [BaseController::class, "health"]);

$app->run();

<?php

declare(strict_types=1);

use App\Core\BaseController;
use App\Core\Config\AppConfig;
use App\Core\ServiceContainerBuilder;
use Slim\Routing\RouteCollectorProxy;
use App\Clientes\Controller as ClientesController;

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

$app->group("/clientes", function (RouteCollectorProxy $group): void {
    $group->get("/", [ClientesController::class, "listarClientes"]);
    $group->post("/", [ClientesController::class, "criarCliente"]);
    $group->get("/{id:[0-9]+}", [ClientesController::class, "obterCliente"]);
    $group->patch("/{id:[0-9]+}", [ClientesController::class, "editarCliente"]);
});

$app->run();

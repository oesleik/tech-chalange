<?php

declare(strict_types=1);

use App\Core\BaseController;
use App\Core\Config\AppConfig;
use App\Core\ServiceContainerBuilder;
use App\Estoque\Controller\EstoqueController;
use Slim\Routing\RouteCollectorProxy;

$containerBuilder = new ServiceContainerBuilder();
$container = $containerBuilder->build();

$appConfig = new AppConfig();
$app = \DI\Bridge\Slim\Bridge::create($container);

$app->addRoutingMiddleware();

$app->addBodyParsingMiddleware();

$app->addErrorMiddleware(
	displayErrorDetails: !$appConfig->getAmbiente()->isProd(),
	logErrors: true,
	logErrorDetails: true
);

$app->get('/', [BaseController::class, "index"]);
$app->get('/health', [BaseController::class, "health"]);

$app->group("/clientes", function (RouteCollectorProxy $group): void {
	$group->get("/", App\Clientes\Controller\ListarClientesController::class);
	$group->post("/", App\Clientes\Controller\CriarClienteController::class);
	$group->get("/{id:[0-9]+}", App\Clientes\Controller\ObterClienteController::class);
	$group->patch("/{id:[0-9]+}", App\Clientes\Controller\EditarClienteController::class);
});

$app->group("/veiculos", function (RouteCollectorProxy $group): void {
	$group->get("/", App\Veiculos\Controller\ListarVeiculosController::class);
	$group->post("/", App\Veiculos\Controller\CriarVeiculoController::class);
	$group->get("/{id:[0-9]+}", App\Veiculos\Controller\ObterVeiculoController::class);
	$group->patch("/{id:[0-9]+}", App\Veiculos\Controller\EditarVeiculoController::class);
});

$app->group("/pecas", function (RouteCollectorProxy $group): void {
    $group->get("/", App\Peca\Controller\ListarPecasController::class);
    $group->post("/", App\Peca\Controller\CriarPecaController::class);
    $group->get("/{id:[0-9]+}", App\Peca\Controller\ObterPecaController::class);
    $group->patch("/{id:[0-9]+}", App\Peca\Controller\EditarPecaController::class);
});

$app->post('/api/estoque/entrada', [EstoqueController::class, 'registrarEntrada']);

$app->run();

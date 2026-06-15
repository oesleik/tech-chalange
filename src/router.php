<?php

declare(strict_types=1);

use App\Core\BaseController;
use App\Core\Config\AppConfig;
use App\Core\ServiceContainerBuilder;
use Slim\Routing\RouteCollectorProxy;
use App\Estoque\Controller\EstoqueController;

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

$app->group("/pecas", function (RouteCollectorProxy $group): void {
    $group->get("/", App\Peca\Controller\ListarPecasController::class);
    $group->post("/", App\Peca\Controller\CriarPecaController::class);
    $group->get("/{id:[0-9]+}", App\Peca\Controller\ObterPecaController::class);
    $group->patch("/{id:[0-9]+}", App\Peca\Controller\EditarPecaController::class);
});

$app->group("/servicos", function (RouteCollectorProxy $group): void {
    $group->get("/", App\Servicos\Controller\ListarServicosController::class);
    $group->post("/", App\Servicos\Controller\CriarServicoController::class);
    $group->get("/{id:[0-9]+}", App\Servicos\Controller\ObterServicoController::class);
    $group->patch("/{id:[0-9]+}", App\Servicos\Controller\EditarServicoController::class);
});

$app->post('/api/estoque/entrada', [EstoqueController::class, 'registrarEntrada']);

$app->group("/ordens-servico", function (RouteCollectorProxy $group): void {
    $group->get("/", App\OrdemServico\Controller\ListarOrdensServicoController::class);
    $group->post("/", App\OrdemServico\Controller\CriarOrdemServicoController::class);
    $group->get("/{id:[0-9]+}", App\OrdemServico\Controller\ObterOrdemServicoController::class);
    $group->patch("/{id:[0-9]+}/situacao", App\OrdemServico\Controller\AtualizarSituacaoController::class);
});
$app->run();

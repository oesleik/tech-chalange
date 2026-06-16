<?php

declare(strict_types=1);

use App\Core\Auth\JwtMiddleware;
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
    logErrorDetails: true,
);

// Rotas públicas
$app->get('/', [BaseController::class, 'index']);
$app->get('/health', [BaseController::class, 'health']);

// Rotas protegidas
$app->group('', function (RouteCollectorProxy $group): void {

    $group->group('/clientes', function (RouteCollectorProxy $g): void {
        $g->get('/', App\Clientes\Controller\ListarClientesController::class);
        $g->post('/', App\Clientes\Controller\CriarClienteController::class);
        $g->get('/{id:[0-9]+}', App\Clientes\Controller\ObterClienteController::class);
        $g->patch('/{id:[0-9]+}', App\Clientes\Controller\EditarClienteController::class);
    });

    $group->group('/veiculos', function (RouteCollectorProxy $g): void {
        $g->get('/', App\Veiculos\Controller\ListarVeiculosController::class);
        $g->post('/', App\Veiculos\Controller\CriarVeiculoController::class);
        $g->get('/{id:[0-9]+}', App\Veiculos\Controller\ObterVeiculoController::class);
        $g->patch('/{id:[0-9]+}', App\Veiculos\Controller\EditarVeiculoController::class);
    });

    $group->group('/pecas', function (RouteCollectorProxy $g): void {
        $g->get('/', App\Peca\Controller\ListarPecasController::class);
        $g->post('/', App\Peca\Controller\CriarPecaController::class);
        $g->get('/{id:[0-9]+}', App\Peca\Controller\ObterPecaController::class);
        $g->patch('/{id:[0-9]+}', App\Peca\Controller\EditarPecaController::class);
    });

    $group->group("/ordens-servico", function (RouteCollectorProxy $group): void {
        $group->get("/", App\OrdemServico\Controller\ListarOrdensServicoController::class);
        $group->post("/", App\OrdemServico\Controller\CriarOrdemServicoController::class);
        $group->get("/{id:[0-9]+}", App\OrdemServico\Controller\ObterOrdemServicoController::class);
        $group->patch("/{id:[0-9]+}/situacao", App\OrdemServico\Controller\AtualizarSituacaoController::class);
    });
   
    $group->post('/api/estoque/entrada', [EstoqueController::class, 'registrarEntrada']);

})->add(JwtMiddleware::class);

$app->run();

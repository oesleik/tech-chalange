<?php

declare(strict_types=1);

use App\Core\Auth\JwtMiddleware;
use App\Core\Auth\OrdemServico\JwtOrdemServicoMiddleware;
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

// Rota pública validada por token, um JWT específico da Ordem de Serviço
$app->group('/email', function (RouteCollectorProxy $group): void {
    $group->get('/ordens-servico/aprovada', [App\OrdemServico\Controller\AtualizarSituacaoEmailController::class, "atualizarParaAprovada"]);
    $group->get('/ordens-servico/rejeitada', [App\OrdemServico\Controller\AtualizarSituacaoEmailController::class, "atualizarParaRejeitada"]);
})->add(JwtOrdemServicoMiddleware::class);

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

    $group->group("/servicos", function (RouteCollectorProxy $group): void {
        $group->get("/", App\Servicos\Controller\ListarServicosController::class);
        $group->post("/", App\Servicos\Controller\CriarServicoController::class);
        $group->get("/{id:[0-9]+}", App\Servicos\Controller\ObterServicoController::class);
        $group->patch("/{id:[0-9]+}", App\Servicos\Controller\EditarServicoController::class);
    });

    $group->group("/ordens-servico", function (RouteCollectorProxy $group): void {
        $group->get("/", App\OrdemServico\Controller\ListarOrdensServicoController::class);
        $group->post("/", App\OrdemServico\Controller\CriarOrdemServicoController::class);
        $group->get("/{id:[0-9]+}", App\OrdemServico\Controller\ObterOrdemServicoController::class);

        $group->get("/proxima", App\OrdemServico\Controller\ObterProximaOrdemServicoController::class);
        $group->put("/{id:[0-9]+}/em-diagnostico", [App\OrdemServico\Controller\AtualizarSituacaoController::class, "atualizarParaEmDiagnostico"]);
        $group->put("/{id:[0-9]+}/aguardando-aprovacao", [App\OrdemServico\Controller\AtualizarSituacaoController::class, "atualizarParaAguardandoAprovacao"]);
        $group->put("/{id:[0-9]+}/em-execucao", [App\OrdemServico\Controller\AtualizarSituacaoController::class, "atualizarParaEmExecucao"]);
        $group->put("/{id:[0-9]+}/finalizada", [App\OrdemServico\Controller\AtualizarSituacaoController::class, "atualizarParaFinalizada"]);
        $group->put("/{id:[0-9]+}/entregue", [App\OrdemServico\Controller\AtualizarSituacaoController::class, "atualizarParaEntregue"]);

        $group->post("/{id:[0-9]+}/pecas", [App\OrdemServico\Controller\EditarItensOrdemServicoController::class, "adicionarPecas"]);
        $group->put("/{id:[0-9]+}/pecas", [App\OrdemServico\Controller\EditarItensOrdemServicoController::class, "atualizarPecas"]);
        $group->post("/{id:[0-9]+}/servicos", [App\OrdemServico\Controller\EditarItensOrdemServicoController::class, "adicionarServicos"]);
        $group->put("/{id:[0-9]+}/servicos", [App\OrdemServico\Controller\EditarItensOrdemServicoController::class, "atualizarServicos"]);

        $group->get("/relatorios/media_tempo_servicos", [App\OrdemServico\Controller\RelatoriosOrdemServicoController::class, "relatorioMediaTempoServicos"]);
    });

    $group->group('/estoque', function (RouteCollectorProxy $g): void {
        $g->post('/entrada', [EstoqueController::class, 'registrarEntrada']);
        $g->post('/baixa', [EstoqueController::class, 'registrarBaixa']);
        $g->get('/pecas/{id:[0-9]+}', [EstoqueController::class, 'consultarEstoque']);
    });

})->add(JwtMiddleware::class);

$app->run();

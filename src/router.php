<?php

declare(strict_types=1);

use App\Core\Auth\JwtMiddleware;
use App\Core\Auth\OrdemServico\JwtOrdemServicoMiddleware;
use App\Core\BaseController;
use App\Core\Config\AppConfig;
use App\Core\ServiceContainerBuilder;
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
$app->get('/consulta/ordem-servico', App\OrdemServico\Presentation\Http\Controller\ConsultarOrdemServicoPorVeiculoEClienteController::class);

// Rota pública validada por token, um JWT específico da Ordem de Serviço
$app->group('/email', function (RouteCollectorProxy $group): void {
    $group->get('/email/ordens-servico/aprovada', [App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoEmailController::class, 'atualizarParaAprovada']);
    $group->get('/email/ordens-servico/rejeitada', [App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoEmailController::class, 'atualizarParaRejeitada']);
})->add(JwtOrdemServicoMiddleware::class);

// Rotas protegidas
$app->group('', function (RouteCollectorProxy $group): void {

    $group->group('/clientes', function (RouteCollectorProxy $g): void {
        $g->get('/', App\Clientes\Presentation\Http\Router\ListarClientesRouter::class);
        $g->post('/', App\Clientes\Presentation\Http\Router\CriarClienteRouter::class);
        $g->get('/{id:[0-9]+}', App\Clientes\Presentation\Http\Router\ObterClienteRouter::class);
        $g->patch('/{id:[0-9]+}', App\Clientes\Presentation\Http\Router\EditarClienteRouter::class);
    });

    $group->group('/veiculos', function (RouteCollectorProxy $g): void {
        $g->get('/', App\Veiculos\Presentation\Http\Router\ListarVeiculoRouter::class);
        $g->post('/', App\Veiculos\Presentation\Http\Router\CriarVeiculoRouter::class);
        $g->get('/{id:[0-9]+}', App\Veiculos\Presentation\Http\Router\ObterVeiculoRouter::class);
        $g->patch('/{id:[0-9]+}', App\Veiculos\Presentation\Http\Router\EditarVeiculoRouter::class);
    });

    $group->group('/pecas', function (RouteCollectorProxy $g): void {
        $g->get('/', App\Peca\Presentation\Http\Router\ListarPecasRouter::class);
        $g->post('/', App\Peca\Presentation\Http\Router\CriarPecaRouter::class);
        $g->get('/{id:[0-9]+}', App\Peca\Presentation\Http\Router\ObterPecaRouter::class);
        $g->patch('/{id:[0-9]+}', App\Peca\Presentation\Http\Router\EditarPecaRouter::class);
    });

    $group->group("/servicos", function (RouteCollectorProxy $group): void {
        $group->get("/", App\Servicos\Controller\ListarServicosController::class);
        $group->post("/", App\Servicos\Controller\CriarServicoController::class);
        $group->get("/{id:[0-9]+}", App\Servicos\Controller\ObterServicoController::class);
        $group->patch("/{id:[0-9]+}", App\Servicos\Controller\EditarServicoController::class);
    });

    $group->group("/ordens-servico", function (RouteCollectorProxy $group): void {
        $group->get("/", App\OrdemServico\Presentation\Http\Router\ListarOrdensServicoRouter::class);
        $group->post("/", App\OrdemServico\Presentation\Http\Router\CriarOrdemServicoRouter::class);
        $group->get("/proxima", App\OrdemServico\Presentation\Http\Router\ObterProximaOrdemServicoRouter::class);
        $group->get("/{id:[0-9]+}", App\OrdemServico\Presentation\Http\Router\ObterOrdemServicoRouter::class);

        $group->put("/{id:[0-9]+}/em-diagnostico", [App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController::class, "atualizarParaEmDiagnostico"]);
        $group->put("/{id:[0-9]+}/aguardando-aprovacao", [App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController::class, "atualizarParaAguardandoAprovacao"]);
        $group->put("/{id:[0-9]+}/em-execucao", [App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController::class, "atualizarParaEmExecucao"]);
        $group->put("/{id:[0-9]+}/finalizada", [App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController::class, "atualizarParaFinalizada"]);
        $group->put("/{id:[0-9]+}/entregue", [App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController::class, "atualizarParaEntregue"]);

        $group->post("/{id:[0-9]+}/pecas", [App\OrdemServico\Presentation\Http\Controller\EditarItensOrdemServicoController::class, "adicionarPecas"]);
        $group->put("/{id:[0-9]+}/pecas", [App\OrdemServico\Presentation\Http\Controller\EditarItensOrdemServicoController::class, "atualizarPecas"]);
        $group->post("/{id:[0-9]+}/servicos", [App\OrdemServico\Presentation\Http\Controller\EditarItensOrdemServicoController::class, "adicionarServicos"]);
        $group->put("/{id:[0-9]+}/servicos", [App\OrdemServico\Presentation\Http\Controller\EditarItensOrdemServicoController::class, "atualizarServicos"]);

        $group->post("/{id:[0-9]+}/enviar-orcamento", App\OrdemServico\Presentation\Http\Controller\EnviarOrcamentoOrdemServicoEmailController::class);

        $group->get("/relatorios/media_tempo_servicos", [App\OrdemServico\Presentation\Http\Controller\RelatoriosOrdemServicoController::class, "relatorioMediaTempoServicos"]);
    });

    $group->group('/estoque', function (RouteCollectorProxy $g): void {
        $g->post('/entrada', App\Estoque\Presentation\Http\Router\RegistrarEntradaEstoqueRouter::class);
        $g->post('/baixa', App\Estoque\Presentation\Http\Router\RegistrarBaixaEstoqueRouter::class);
        $g->get('/pecas/{id:[0-9]+}', App\Estoque\Presentation\Http\Router\ConsultarEstoquePorPecaRouter::class);
    });

})->add(JwtMiddleware::class);

return $app;

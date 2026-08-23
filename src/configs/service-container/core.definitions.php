<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Core\Config\EmailConfig;
use App\Core\Email\EmailService;
use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Core\Infrastructure\Persistence\DatabaseConnection;
use App\Core\Presentation\Http\JsonPresenter;
use App\Core\Presentation\Http\PresenterInterface;
use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;
use App\Veiculos\Infrastructure\Persistence\VeiculoGateway;
use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Infrastructure\Persistence\PecaGateway;
use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Infrastructure\Persistence\ClienteGateway;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use App\Clientes\Application\UseCase\CriarCliente\CriarClienteUseCaseInterface;
use App\Clientes\Application\UseCase\CriarCliente\CriarClienteUseCase;
use App\Clientes\Application\UseCase\EditarCliente\EditarClienteUseCaseInterface;
use App\Clientes\Application\UseCase\EditarCliente\EditarClienteUseCase;
use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCaseInterface;
use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCase;
use App\Clientes\Application\UseCase\ObterCliente\ObterClienteUseCaseInterface;
use App\Clientes\Application\UseCase\ObterCliente\ObterClienteUseCase;
use App\Clientes\Presentation\Http\Controller\CriarClienteControllerInterface;
use App\Clientes\Presentation\Http\Controller\CriarClienteController;
use App\Clientes\Presentation\Http\Controller\EditarClienteControllerInterface;
use App\Clientes\Presentation\Http\Controller\EditarClienteController;
use App\Clientes\Presentation\Http\Controller\ListarClientesControllerInterface;
use App\Clientes\Presentation\Http\Controller\ListarClientesController;
use App\Clientes\Presentation\Http\Controller\ObterClienteControllerInterface;
use App\Clientes\Presentation\Http\Controller\ObterClienteController;
use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Infrastructure\Persistence\EstoqueGateway;
use App\Estoque\Application\UseCase\RegistrarEntradaEstoque\RegistrarEntradaEstoqueUseCaseInterface;
use App\Estoque\Application\UseCase\RegistrarEntradaEstoque\RegistrarEntradaEstoqueUseCase;
use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueUseCaseInterface;
use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueUseCase;
use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaUseCaseInterface;
use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaUseCase;
use App\Estoque\Presentation\Http\Controller\RegistrarEntradaEstoqueControllerInterface;
use App\Estoque\Presentation\Http\Controller\RegistrarEntradaEstoqueController;
use App\Estoque\Presentation\Http\Controller\RegistrarBaixaEstoqueControllerInterface;
use App\Estoque\Presentation\Http\Controller\RegistrarBaixaEstoqueController;
use App\Estoque\Presentation\Http\Controller\ConsultarEstoquePorPecaControllerInterface;
use App\Estoque\Presentation\Http\Controller\ConsultarEstoquePorPecaController;
use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoUseCase;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoUseCaseInterface;
use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoUseCase;
use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoUseCaseInterface;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarPecasOrdemServicoUseCase;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarPecasOrdemServicoUseCaseInterface;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarServicosOrdemServicoUseCase;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarServicosOrdemServicoUseCaseInterface;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\GerarRelatorioMediaTempoUseCase;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\GerarRelatorioMediaTempoUseCaseInterface;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\RelatorioMediaTempoRepositoryInterface;
use App\OrdemServico\Application\UseCase\ListarOrdensServico\ListarOrdensServicoUseCase;
use App\OrdemServico\Application\UseCase\ListarOrdensServico\ListarOrdensServicoUseCaseInterface;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoUseCase;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoUseCaseInterface;
use App\OrdemServico\Application\UseCase\ObterProximaOrdemServico\ObterProximaOrdemServicoUseCase;
use App\OrdemServico\Application\UseCase\ObterProximaOrdemServico\ObterProximaOrdemServicoUseCaseInterface;
use App\OrdemServico\Infrastructure\Persistence\ItensOrdemServicoGateway;
use App\OrdemServico\Infrastructure\Persistence\OrdemServicoGateway;
use App\OrdemServico\Infrastructure\Persistence\RelatorioMediaTempoRepository;
use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController;
use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoEmailController;
use App\OrdemServico\Presentation\Http\Controller\ConsultarOrdemServicoPorVeiculoEClienteController;
use App\OrdemServico\Presentation\Http\Controller\CriarOrdemServicoController;
use App\OrdemServico\Presentation\Http\Controller\EditarItensOrdemServicoController;
use App\OrdemServico\Presentation\Http\Controller\EnviarOrcamentoOrdemServicoEmailController;
use App\OrdemServico\Presentation\Http\Controller\ListarOrdensServicoController;
use App\OrdemServico\Presentation\Http\Controller\ObterOrdemServicoController;
use App\OrdemServico\Presentation\Http\Controller\ObterProximaOrdemServicoController;
use App\OrdemServico\Presentation\Http\Controller\RelatoriosOrdemServicoController;

return [
    Symfony\Component\Validator\Validator\ValidatorInterface::class => fn() => Symfony\Component\Validator\Validation::createValidatorBuilder()->getValidator(),
    Symfony\Component\Serializer\SerializerInterface::class => function () {
        $docBlockExtractor = new Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor();
        $reflectionExtractor = new Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor();

        $typeExtractor = new Symfony\Component\PropertyInfo\PropertyInfoExtractor(
            [$docBlockExtractor, $reflectionExtractor],
            [$docBlockExtractor, $reflectionExtractor],
            [$reflectionExtractor],
            [$reflectionExtractor],
            [$reflectionExtractor]
        );

        return new Symfony\Component\Serializer\Serializer([
            new Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer(),
            new Symfony\Component\Serializer\Normalizer\DateTimeNormalizer(),
            new Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer(),
            new Symfony\Component\Serializer\Normalizer\ArrayDenormalizer(),
            new Symfony\Component\Serializer\Normalizer\ObjectNormalizer(
                propertyTypeExtractor: $typeExtractor
            ),
        ], [
            new Symfony\Component\Serializer\Encoder\JsonEncoder(),
        ]);
    },
    Symfony\Component\Serializer\Normalizer\NormalizerInterface::class => DI\get(Symfony\Component\Serializer\SerializerInterface::class),
    Symfony\Component\Serializer\Normalizer\DenormalizerInterface::class => DI\get(Symfony\Component\Serializer\SerializerInterface::class),

    ResponseFactoryInterface::class => DI\create(ResponseFactory::class),

    EmailConfig::class => fn() => new EmailConfig(),
    EmailService::class => fn(\DI\Container $c) => new EmailService(
        $c->get(EmailConfig::class),
    ),
    DbConnectionInterface::class => fn(\DI\Container $c) => new DatabaseConnection(
        $c->get(AppDatabase::class),
    ),
    PresenterInterface::class => fn(\DI\Container $c) => new JsonPresenter(
        $c->get(Symfony\Component\Serializer\SerializerInterface::class),
    ),
    ClienteGatewayInterface::class => fn(\DI\Container $c) => new ClienteGateway($c->get(DbConnectionInterface::class)),
    VeiculoGatewayInterface::class => fn(\DI\Container $c) => new VeiculoGateway($c->get(DbConnectionInterface::class)),
    PecaGatewayInterface::class => fn(\DI\Container $c) => new PecaGateway($c->get(DbConnectionInterface::class)),
    CriarClienteUseCaseInterface::class => fn(\DI\Container $c) => new CriarClienteUseCase($c->get(ClienteGatewayInterface::class)),
    EditarClienteUseCaseInterface::class => fn(\DI\Container $c) => new EditarClienteUseCase($c->get(ClienteGatewayInterface::class)),
    ListarClientesUseCaseInterface::class => fn(\DI\Container $c) => new ListarClientesUseCase($c->get(ClienteGatewayInterface::class)),
    ObterClienteUseCaseInterface::class => fn(\DI\Container $c) => new ObterClienteUseCase($c->get(ClienteGatewayInterface::class)),
    // Gateway
    EstoqueGatewayInterface::class => fn(\DI\Container $c) => new EstoqueGateway(
        $c->get(DbConnectionInterface::class),
        $c->get(AppDatabase::class),
    ),
    // Use Cases
    RegistrarEntradaEstoqueUseCaseInterface::class => fn(\DI\Container $c) => new RegistrarEntradaEstoqueUseCase(
        $c->get(EstoqueGatewayInterface::class),
    ),
    RegistrarBaixaEstoqueUseCaseInterface::class => fn(\DI\Container $c) => new RegistrarBaixaEstoqueUseCase(
        $c->get(EstoqueGatewayInterface::class),
    ),
    ConsultarEstoquePorPecaUseCaseInterface::class => fn(\DI\Container $c) => new ConsultarEstoquePorPecaUseCase(
        $c->get(EstoqueGatewayInterface::class),
    ),
    CriarClienteControllerInterface::class => fn(\DI\Container $c) => new CriarClienteController(
        $c->get(CriarClienteUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    EditarClienteControllerInterface::class => fn(\DI\Container $c) => new EditarClienteController(
        $c->get(EditarClienteUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    ListarClientesControllerInterface::class => fn(\DI\Container $c) => new ListarClientesController(
        $c->get(ListarClientesUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    ObterClienteControllerInterface::class => fn(\DI\Container $c) => new ObterClienteController(
        $c->get(ObterClienteUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    // Controllers
    RegistrarEntradaEstoqueControllerInterface::class => fn(\DI\Container $c) => new RegistrarEntradaEstoqueController(
        $c->get(RegistrarEntradaEstoqueUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    RegistrarBaixaEstoqueControllerInterface::class => fn(\DI\Container $c) => new RegistrarBaixaEstoqueController(
        $c->get(RegistrarBaixaEstoqueUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    ConsultarEstoquePorPecaControllerInterface::class => fn(\DI\Container $c) => new ConsultarEstoquePorPecaController(
        $c->get(ConsultarEstoquePorPecaUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),

    // OrdemServico — Gateways
    OrdemServicoGatewayInterface::class => fn(\DI\Container $c) => new OrdemServicoGateway($c->get(AppDatabase::class)),
    ItensOrdemServicoGatewayInterface::class => fn(\DI\Container $c) => new ItensOrdemServicoGateway(
        $c->get(AppDatabase::class),
        $c->get(App\Core\Database\TransactionHandler::class),
        $c->get(OrdemServicoGatewayInterface::class),
    ),
    RelatorioMediaTempoRepositoryInterface::class => fn(\DI\Container $c) => new RelatorioMediaTempoRepository($c->get(AppDatabase::class)),

    // OrdemServico — Use Cases
    CriarOrdemServicoUseCaseInterface::class => fn(\DI\Container $c) => new CriarOrdemServicoUseCase($c->get(OrdemServicoGatewayInterface::class)),
    ListarOrdensServicoUseCaseInterface::class => fn(\DI\Container $c) => new ListarOrdensServicoUseCase($c->get(OrdemServicoGatewayInterface::class)),
    ObterOrdemServicoUseCaseInterface::class => fn(\DI\Container $c) => new ObterOrdemServicoUseCase(
        $c->get(OrdemServicoGatewayInterface::class),
        $c->get(ItensOrdemServicoGatewayInterface::class),
    ),
    ObterProximaOrdemServicoUseCaseInterface::class => fn(\DI\Container $c) => new ObterProximaOrdemServicoUseCase(
        $c->get(OrdemServicoGatewayInterface::class),
        $c->get(ItensOrdemServicoGatewayInterface::class),
    ),
    AtualizarSituacaoUseCaseInterface::class => fn(\DI\Container $c) => new AtualizarSituacaoUseCase($c->get(OrdemServicoGatewayInterface::class)),
    EditarPecasOrdemServicoUseCaseInterface::class => fn(\DI\Container $c) => new EditarPecasOrdemServicoUseCase(
        $c->get(OrdemServicoGatewayInterface::class),
        $c->get(ItensOrdemServicoGatewayInterface::class),
    ),
    EditarServicosOrdemServicoUseCaseInterface::class => fn(\DI\Container $c) => new EditarServicosOrdemServicoUseCase(
        $c->get(OrdemServicoGatewayInterface::class),
        $c->get(ItensOrdemServicoGatewayInterface::class),
    ),
    GerarRelatorioMediaTempoUseCaseInterface::class => fn(\DI\Container $c) => new GerarRelatorioMediaTempoUseCase($c->get(RelatorioMediaTempoRepositoryInterface::class)),

    // OrdemServico — Controllers
    CriarOrdemServicoController::class => fn(\DI\Container $c) => new CriarOrdemServicoController(
        $c->get(CriarOrdemServicoUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    ListarOrdensServicoController::class => fn(\DI\Container $c) => new ListarOrdensServicoController(
        $c->get(ListarOrdensServicoUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    ObterOrdemServicoController::class => fn(\DI\Container $c) => new ObterOrdemServicoController(
        $c->get(ObterOrdemServicoUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    ObterProximaOrdemServicoController::class => fn(\DI\Container $c) => new ObterProximaOrdemServicoController(
        $c->get(ObterProximaOrdemServicoUseCaseInterface::class),
        $c->get(PresenterInterface::class),
        $c->get(App\Core\Config\AppConfig::class),
    ),
    AtualizarSituacaoController::class => fn(\DI\Container $c) => new AtualizarSituacaoController(
        $c->get(AtualizarSituacaoUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    AtualizarSituacaoEmailController::class => fn(\DI\Container $c) => new AtualizarSituacaoEmailController(
        $c->get(AtualizarSituacaoController::class),
    ),
    EditarItensOrdemServicoController::class => fn(\DI\Container $c) => new EditarItensOrdemServicoController(
        $c->get(EditarPecasOrdemServicoUseCaseInterface::class),
        $c->get(EditarServicosOrdemServicoUseCaseInterface::class),
        $c->get(ObterOrdemServicoUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    EnviarOrcamentoOrdemServicoEmailController::class => fn(\DI\Container $c) => new EnviarOrcamentoOrdemServicoEmailController(
        $c->get(App\OrdemServico\Service\EnviarOrcamentoOrdemServicoEmailService::class),
        $c->get(OrdemServicoGatewayInterface::class),
        $c->get(ObterClienteUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    RelatoriosOrdemServicoController::class => fn(\DI\Container $c) => new RelatoriosOrdemServicoController(
        $c->get(GerarRelatorioMediaTempoUseCaseInterface::class),
        $c->get(PresenterInterface::class),
    ),
    ConsultarOrdemServicoPorVeiculoEClienteController::class => fn(\DI\Container $c) => new ConsultarOrdemServicoPorVeiculoEClienteController(
        $c->get(ListarClientesUseCaseInterface::class),
        $c->get(App\Veiculos\Application\UseCase\ObterVeiculoPorPlaca\ObterVeiculoPorPlacaUseCase::class),
        $c->get(OrdemServicoGatewayInterface::class),
        $c->get(ItensOrdemServicoGatewayInterface::class),
        $c->get(PresenterInterface::class),
    ),
];

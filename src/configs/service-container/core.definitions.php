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
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
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
    VeiculoGatewayInterface::class => fn(\DI\Container $c) => new VeiculoGateway($c->get(DbConnectionInterface::class)),
    PecaGatewayInterface::class => fn(\DI\Container $c) => new PecaGateway($c->get(DbConnectionInterface::class)),
    // Gateway
    EstoqueGatewayInterface::class => fn(\DI\Container $c) => new EstoqueGateway(
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
];

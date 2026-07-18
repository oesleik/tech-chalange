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
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

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
];

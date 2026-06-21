<?php

declare(strict_types=1);

use App\Clientes\Service\ClienteService;
use App\Core\Auth\OrdemServico\JwtOrdemServicoService;
use App\Core\Config\AppConfig;
use App\Core\Config\EmailConfig;
use App\Core\Email\EmailService;
use App\OrdemServico\Service\EnviarOrcamentoOrdemServicoEmailService;
use App\OrdemServico\Service\ItensOrdemServicoService;
use App\OrdemServico\Service\OrdemServicoService;
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

    EnviarOrcamentoOrdemServicoEmailService::class => fn(\DI\Container $c) => new EnviarOrcamentoOrdemServicoEmailService(
        ordemServicoService: $c->get(OrdemServicoService::class),
        itensOrdemServicoService: $c->get(ItensOrdemServicoService::class),
        clienteService: $c->get(ClienteService::class),
        jwtOrdemServicoService: $c->get(JwtOrdemServicoService::class),
        emailService: $c->get(EmailService::class),
        appConfig: $c->get(AppConfig::class),
    ),
];

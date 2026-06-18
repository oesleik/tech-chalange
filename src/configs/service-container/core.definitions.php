<?php

declare(strict_types=1);

use App\Core\Auth\JwtMiddleware;
use App\Core\Auth\JwtService;
use App\Core\Config\EmailConfig;
use App\Core\Config\JwtConfig;
use App\Core\Email\EmailService;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

return [
    Symfony\Component\Validator\Validator\ValidatorInterface::class => fn() => Symfony\Component\Validator\Validation::createValidatorBuilder()->getValidator(),
    Symfony\Component\Serializer\SerializerInterface::class => fn() => new Symfony\Component\Serializer\Serializer([
        new Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer(),
        new Symfony\Component\Serializer\Normalizer\DateTimeNormalizer(),
        new Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer(),
        new Symfony\Component\Serializer\Normalizer\ObjectNormalizer(),
    ], [
        new Symfony\Component\Serializer\Encoder\JsonEncoder(),
    ]),
    Symfony\Component\Serializer\Normalizer\NormalizerInterface::class => DI\get(Symfony\Component\Serializer\SerializerInterface::class),
    Symfony\Component\Serializer\Normalizer\DenormalizerInterface::class => DI\get(Symfony\Component\Serializer\SerializerInterface::class),

    ResponseFactoryInterface::class => DI\create(ResponseFactory::class),

    EmailConfig::class => fn() => new EmailConfig(),
    EmailService::class => fn(\DI\Container $c) => new EmailService(
        $c->get(EmailConfig::class),
    ),
];

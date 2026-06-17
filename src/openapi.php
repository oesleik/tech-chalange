<?php

declare(strict_types=1);

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API Tech Chalange',
    description: 'API documentation'
)]
#[OA\Server(
    url: 'http://localhost',
    description: 'Development'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\OpenApi(
    security: [['bearerAuth' => []]]
)]
// Declarando na mão para setar a ordem
#[OA\Tag(name: "Health")]
#[OA\Tag(name: "Clientes")]
#[OA\Tag(name: "Veículos")]
#[OA\Tag(name: "Peças")]
#[OA\Tag(name: "Serviços")]
#[OA\Tag(name: "Estoque")]
#[OA\Tag(name: "Ordens de Serviço")]
#[OA\Tag(name: "Ordens de Serviço - Situação")]
final class openapi {}

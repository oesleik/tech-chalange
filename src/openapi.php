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
final class openapi
{
}

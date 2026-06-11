<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\AppDatabase;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class BaseController {
    public function index(ResponseInterface $response): ResponseInterface {
        $response->getBody()->write(json_encode([
            'status'  => 'ok',
            'message' => 'API disponível',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Get(
        path: '/health',
        tags: ["Health"],
        security: []
    )]
    #[OA\Response(
        response: 200,
        description: 'Servidor está funcionando',
    )]
    public function health(ResponseInterface $response, AppDatabase $db): ResponseInterface {
        $db->query('SELECT 1');

        $response->getBody()->write(json_encode([
            'status'   => 'ok',
            'database' => 'connected',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}

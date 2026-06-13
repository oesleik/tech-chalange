<?php

declare(strict_types=1);

namespace App\Peca\Controller;

use App\Peca\Contract\PecaResponse;
use App\Peca\Contract\ListarPecasResponse;
use App\Peca\Service\PecaService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class ListarPecasController {
    #[OA\Get(
        path: '/pecas/',
        operationId: 'listarPecas',
        summary: 'Listar peças cadastradas',
        tags: ['Peças']
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de peças encontradas',
        content: new OA\JsonContent(ref: '#/components/schemas/ListarPecasResponse')
    )]
    public function __invoke(
        ResponseInterface $response,
        ContractResolver $contractResolver,
        PecaService $service,
    ): ResponseInterface {
        $pecas = $service->listarPecas();

        $output = new ListarPecasResponse(
            pecas: array_map([PecaResponse::class, "fromPecaModel"], $pecas)
        );

        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
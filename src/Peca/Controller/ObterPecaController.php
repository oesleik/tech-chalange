<?php

declare(strict_types=1);

namespace App\Peca\Controller;

use App\Peca\Contract\PecaResponse;
use App\Peca\Service\PecaService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class ObterPecaController {
    #[OA\Get(
        path: '/pecas/{id}',
        operationId: 'obterPeca',
        summary: 'Obter detalhes de uma peça',
        tags: ['Peças']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Peça encontrada',
        content: new OA\JsonContent(ref: '#/components/schemas/PecaResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Peça não encontrada'
    )]
    public function __invoke(
        int $id,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        PecaService $service,
    ): ResponseInterface {
        $peca = $service->obterPecaPorId($id);

        if (!$peca) {
            return $response->withStatus(404, "Peça não encontrada");
        }

        $output = PecaResponse::fromPecaModel($peca);
        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

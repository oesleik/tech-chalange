<?php

declare(strict_types=1);

namespace App\Veiculos\Controller;

use App\Veiculos\Contract\VeiculoResponse;
use App\Veiculos\Service\VeiculoService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class ObterVeiculoController {
    #[OA\Get(
        path: '/veiculos/{id}',
        operationId: 'obterVeiculo',
        summary: 'Obter detalhes de um veículo',
        tags: ['Veiculos']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Veículo encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/VeiculoResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Veículo não encontrado'
    )]
    public function __invoke(
        int $id,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        VeiculoService $service,
    ): ResponseInterface {
        $veiculo = $service->obterVeiculoPorId($id);
        if (!$veiculo) {
            return $response->withStatus(404, "Veículo não encontrado");
        }
        $output = VeiculoResponse::fromVeiculoModel($veiculo);
        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

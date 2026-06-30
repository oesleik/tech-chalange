<?php

declare(strict_types=1);

namespace App\Clientes\Controller;

use App\Clientes\Contract\ClienteResponse;
use App\Clientes\Service\ClienteService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class ObterClienteController {
    #[OA\Get(
        path: '/clientes/{id}',
        operationId: 'obterCliente',
        summary: 'Obter detalhes de um cliente',
        tags: ['Clientes']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Cliente encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/ClienteResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Cliente não encontrado'
    )]
    public function __invoke(
        int $id,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        ClienteService $service,
    ): ResponseInterface {
        $cliente = $service->obterClientePorId($id);

        if (!$cliente) {
            return $response->withStatus(404, "Cliente não encontrado");
        }

        $output = ClienteResponse::fromClienteModel($cliente, false);
        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }

}

<?php

declare(strict_types=1);

namespace App\Servicos\Controller;

use App\Servicos\Contract\ServicoResponse;
use App\Servicos\Service\ServicosService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class ObterServicoController {
    #[OA\Get(
        path: '/servicos/{id}',
        operationId: 'obterServico',
        summary: 'Obter detalhes de um serviço',
        tags: ['Serviços']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Serviço encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/ServicoResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Serviço não encontrado'
    )]
    public function __invoke(
        int $id,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        ServicosService $service,
    ): ResponseInterface {
        $servico = $service->obterServicoPorId($id);

        if (!$servico) {
            return $response->withStatus(404, "Serviço não encontrado");
        }

        $output = ServicoResponse::fromServicoModel($servico);
        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

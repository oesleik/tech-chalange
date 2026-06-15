<?php

declare(strict_types=1);

namespace App\Servicos\Controller;

use App\Servicos\Contract\ServicoResponse;
use App\Servicos\Contract\ListarServicosResponse;
use App\Servicos\Service\ServicosService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class ListarServicosController {
    #[OA\Get(
        path: '/servicos/',
        operationId: 'listarServicos',
        summary: 'Listar serviços cadastrados',
        tags: ['Serviços']
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de serviços encontradas',
        content: new OA\JsonContent(ref: '#/components/schemas/ListarServicosResponse')
    )]
    public function __invoke(
        ResponseInterface $response,
        ContractResolver $contractResolver,
        ServicosService $service,
    ): ResponseInterface {
        $servicos = $service->listarServicos();

        $output = new ListarServicosResponse(
            servicos: array_map([ServicoResponse::class, "fromServicoModel"], $servicos)
        );

        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

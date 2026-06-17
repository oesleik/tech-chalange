<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Contract\OrdemServicoResponse;
use App\OrdemServico\Contract\ListarOrdensServicoResponse;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class ListarOrdensServicoController {
    #[OA\Get(
        path: '/ordens-servico/',
        operationId: 'listarOrdensServico',
        summary: 'Listar todas as Ordens de Serviço',
        tags: ['Ordens de Serviço']
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de Ordens de Serviço',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrdemServicoResponse')
        )
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        OrdemServicoService $service,
    ): ResponseInterface {
        try {
            $ordensServico = $service->listarOrdensServico();
            $output = new ListarOrdensServicoResponse(
                ordensServico: array_map(fn($os) => OrdemServicoResponse::fromModel($os), $ordensServico)
            );

            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['erro' => $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}

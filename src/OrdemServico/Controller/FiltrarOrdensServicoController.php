<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Contract\OrdemServicoResumidaResponse;
use App\OrdemServico\Contract\ListarOrdensServicoResponse;
use App\OrdemServico\Contract\FiltrarOrdensServicoRequest;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class FiltrarOrdensServicoController {
    #[OA\Get(
        path: '/ordens-servico/filtrar',
        operationId: 'filtrarOrdensServico',
        summary: 'Filtrar Ordens de Serviço',
        description: 'Filtra ordens de serviço por situação, cliente e/ou veículo',
        tags: ['Ordens de Serviço']
    )]
    #[OA\Parameter(name: 'situacao', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'clienteId', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'veiculoId', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: 'Lista de Ordens de Serviço filtradas',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrdemServicoResponse')
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Dados inválidos'
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        OrdemServicoService $service,
    ): ResponseInterface {
        try {
            $queryParams = $request->getQueryParams() ?? [];
            $req = $contractResolver->fromArray($queryParams, FiltrarOrdensServicoRequest::class);

            $filtroOrdemServico = $req->toFiltroOrdemServico();
            $ordensServico = $service->filtrarOrdensServico($filtroOrdemServico);

            $output = new ListarOrdensServicoResponse(
                ordensServico: array_map(fn($os) => OrdemServicoResumidaResponse::fromModel($os), $ordensServico)
            );

            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['erro' => $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}

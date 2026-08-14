<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Contract\ListarOrdensServicoResponse;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationErrorResponse;
use App\OrdemServico\Contract\OrdemServicoResumidaResponse;
use App\OrdemServico\Contract\OrdensServicoFiltros;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
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
    #[OA\Parameter(
        name: 'situacao',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: SituacaoOrdemServicoEnum::class),
        description: 'Filtro por situação da ordem de serviço'
    )]
    #[OA\Parameter(
        name: 'id_cliente',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer'),
        description: 'Filtro por cliente da ordem de serviço'
    )]
    #[OA\Parameter(
        name: 'id_veiculo',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer'),
        description: 'Filtro por veículo da ordem de serviço'
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de Ordens de Serviço',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrdemServicoResumidaResponse')
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Filtros inválidos'
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        OrdemServicoService $service,
    ): ResponseInterface {
        try {
            $queryParams = $request->getQueryParams() ?? [];
            $queryParams["id_cliente"] = !empty($queryParams["id_cliente"]) ? intval($queryParams["id_cliente"]) : null;
            $queryParams["id_veiculo"] = !empty($queryParams["id_veiculo"]) ? intval($queryParams["id_veiculo"]) : null;
            $filtros = $contractResolver->fromArray($queryParams, OrdensServicoFiltros::class);

            $ordensServico = $service->listarOrdensServico($filtros->toFiltroModel());

            $output = new ListarOrdensServicoResponse(
                ordens_servico: array_map(fn($os) => OrdemServicoResumidaResponse::fromModel($os), $ordensServico)
            );

            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}

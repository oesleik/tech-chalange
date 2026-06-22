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
    #[OA\Post(
        path: '/ordens-servico/filtrar',
        operationId: 'filtrarOrdensServico',
        summary: 'Filtrar Ordens de Serviço',
        description: 'Filtra ordens de serviço por situação, cliente e/ou veículo',
        tags: ['Ordens de Serviço']
    )]
    #[OA\RequestBody(
        description: 'Filtros para busca',
        content: new OA\JsonContent(ref: '#/components/schemas/FiltrarOrdensServicoRequest')
    )]
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
            $payload = json_decode($request->getBody()->getContents(), true) ?? [];
            $req = $contractResolver->fromArray($payload, FiltrarOrdensServicoRequest::class);

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

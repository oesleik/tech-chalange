<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Contract\OrdemServicoCompletaResponse;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use App\OrdemServico\Service\ItensOrdemServicoService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class ObterOrdemServicoController {
    #[OA\Get(
        path: '/ordens-servico/{id}',
        operationId: 'obterOrdemServico',
        summary: 'Obter detalhes de uma Ordem de Serviço',
        tags: ['Ordens de Serviço']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Detalhes da Ordem de Serviço',
        content: new OA\JsonContent(ref: '#/components/schemas/OrdemServicoCompletaResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Ordem de Serviço não encontrada'
    )]
    public function __invoke(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        OrdemServicoService $service,
        ItensOrdemServicoService $itensService,
    ): ResponseInterface {
        $ordemServico = $service->obterOrdemServicoPorId($id);

        if (!$ordemServico) {
            $response->getBody()->write(json_encode(['erro' => 'Ordem de Serviço não encontrada']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $pecas = $itensService->obterPecasPorIdOrdemServico($ordemServico->getId());
        $servicos = $itensService->obterServicosPorIdOrdemServico($ordemServico->getId());

        $output = OrdemServicoCompletaResponse::fromModel($ordemServico, $pecas, $servicos);
        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

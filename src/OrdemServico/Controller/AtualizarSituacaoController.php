<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Contract\AtualizarSituacaoRequest;
use App\OrdemServico\Contract\OrdemServicoResponse;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class AtualizarSituacaoController {
    #[OA\Patch(
        path: '/ordens-servico/{id}/situacao',
        operationId: 'atualizarSituacao',
        summary: 'Atualizar situação de uma Ordem de Serviço',
        tags: ['Ordens de Serviço']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/AtualizarSituacaoRequest'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Situação atualizada com sucesso',
        content: new OA\JsonContent(ref: '#/components/schemas/OrdemServicoResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Ordem de Serviço não encontrada'
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        OrdemServicoService $service,
    ): ResponseInterface {
        try {
            $id = (int) $request->getAttribute('id');
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, AtualizarSituacaoRequest::class);

            $ordemServico = $service->obterOrdemServicoPorId($id);
            if (!$ordemServico) {
                $response->getBody()->write(json_encode(['erro' => 'Ordem de Serviço não encontrada']));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $novaSituacao = SituacaoOrdemServicoEnum::from($req->situacao);
            $service->atualizarSituacao($id, $novaSituacao);

            $ordemServicoAtualizada = $service->obterOrdemServicoPorId($id);
            $output = OrdemServicoResponse::fromModel($ordemServicoAtualizada);

            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['erro' => $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
}

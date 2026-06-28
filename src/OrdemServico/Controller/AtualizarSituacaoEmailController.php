<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

#[OA\Put(
    path: '/email/ordens-servico/aprovada',
    operationId: 'atualizarParaAprovada',
    summary: 'Atualizar ordem de serviço para aprovada',
    tags: ['Ordens de Serviço - Situação']
)]
#[OA\Put(
    path: '/email/ordens-servico/rejeitada',
    operationId: 'atualizarParaRejeitada',
    summary: 'Atualizar ordem de serviço para rejeitada',
    tags: ['Ordens de Serviço - Situação']
)]
#[OA\Response(
    response: 200,
    description: 'Ordem de Serviço atualizada com sucesso',
    content: new OA\JsonContent(ref: '#/components/schemas/OrdemServicoResumidaResponse')
)]
#[OA\Response(response: 404, description: 'Ordem de Serviço não encontrada')]
#[OA\Response(response: 409, description: 'Situação atual da Ordem de Serviço não permite aprovação')]
#[OA\Response(response: 422, description: 'Ordem de serviço não identificada')]
class AtualizarSituacaoEmailController {
    public function __construct(
        private AtualizarSituacaoController $attSituacaoController,
    ) {}

    public function atualizarParaAprovada(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->alterarSituacaoEmail($request, $response, SituacaoOrdemServicoEnum::APROVADA);
    }

    public function atualizarParaRejeitada(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->alterarSituacaoEmail($request, $response, SituacaoOrdemServicoEnum::REJEITADA);
    }

    private function alterarSituacaoEmail(
        ServerRequestInterface $request,
        ResponseInterface $response,
        SituacaoOrdemServicoEnum $novaSituacao
    ) {
        $claims = $request->getAttribute('jwt_claims', []);
        $idOrdemServico = $claims['id_ordem_servico'] ?? null;

        if (!is_int($idOrdemServico) && !ctype_digit((string) $idOrdemServico)) {
            return $response->withStatus(422, "Ordem de serviço não identificada");
        }

        return $this->attSituacaoController->alterarSituacao(intval($idOrdemServico), $novaSituacao, $response);
    }
}

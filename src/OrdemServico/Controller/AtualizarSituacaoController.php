<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Contract\OrdemServicoResumidaResponse;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\SituacaoBloqueadaException;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

#[OA\Put(
    path: '/ordens-servico/{id}/em-diagnostico',
    operationId: 'atualizarParaEmDiagnostico',
    summary: 'Atualizar ordem de serviço para em diagnóstico',
    tags: ['Ordens de Serviço - Situação']
)]
#[OA\Put(
    path: '/ordens-servico/{id}/aguardando-aprovacao',
    operationId: 'atualizarParaAguardandoAprovacao',
    summary: 'Atualizar ordem de serviço para aguardando aprovação',
    tags: ['Ordens de Serviço - Situação']
)]
#[OA\Put(
    path: '/ordens-servico/{id}/em-execucao',
    operationId: 'atualizarParaEmExecucao',
    summary: 'Atualizar ordem de serviço para em execução',
    tags: ['Ordens de Serviço - Situação']
)]
#[OA\Put(
    path: '/ordens-servico/{id}/finalizada',
    operationId: 'atualizarParaFinalizada',
    summary: 'Atualizar ordem de serviço para finalizada',
    tags: ['Ordens de Serviço - Situação']
)]
#[OA\Put(
    path: '/ordens-servico/{id}/entregue',
    operationId: 'atualizarParaEntregue',
    summary: 'Atualizar ordem de serviço para entregue',
    tags: ['Ordens de Serviço - Situação']
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'integer')
)]
#[OA\Response(
    response: 200,
    description: 'Situação atualizada com sucesso',
    content: new OA\JsonContent(ref: '#/components/schemas/OrdemServicoResumidaResponse')
)]
#[OA\Response(
    response: 404,
    description: 'Ordem de Serviço não encontrada'
)]
#[OA\Response(
    response: 409,
    description: 'Ordem de Serviço não atualizada'
)]
class AtualizarSituacaoController {
    public function __construct(
        private ContractResolver $contractResolver,
        private OrdemServicoService $service,
    ) {}

    public function atualizarParaEmDiagnostico(int $id, ResponseInterface $response): ResponseInterface {
        return $this->alterarSituacao($id, SituacaoOrdemServicoEnum::EM_DIAGNOSTICO, $response);
    }

    public function atualizarParaAguardandoAprovacao(int $id, ResponseInterface $response): ResponseInterface {
        return $this->alterarSituacao($id, SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO, $response);
    }

    public function atualizarParaEmExecucao(int $id, ResponseInterface $response): ResponseInterface {
        return $this->alterarSituacao($id, SituacaoOrdemServicoEnum::EM_EXECUCAO, $response);
    }

    public function atualizarParaFinalizada(int $id, ResponseInterface $response): ResponseInterface {
        return $this->alterarSituacao($id, SituacaoOrdemServicoEnum::FINALIZADA, $response);
    }

    public function atualizarParaEntregue(int $id, ResponseInterface $response): ResponseInterface {
        return $this->alterarSituacao($id, SituacaoOrdemServicoEnum::ENTREGUE, $response);
    }

    public function alterarSituacao(
        int $id,
        SituacaoOrdemServicoEnum $novaSituacao,
        ResponseInterface $response
    ): ResponseInterface {
        $ordemServico = $this->service->obterOrdemServicoPorId($id);

        if (!$ordemServico) {
            return $response->withStatus(404, "Ordem de serviço não encontrada");
        }

        try {
            $ordemServico = $this->service->atualizarSituacao($ordemServico, $novaSituacao);
        } catch (SituacaoBloqueadaException $e) {
            return $response->withStatus(409, $e->getMessage());
        }

        $output = OrdemServicoResumidaResponse::fromModel($ordemServico);
        $response->getBody()->write($this->contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

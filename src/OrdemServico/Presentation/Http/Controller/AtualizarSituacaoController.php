<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoInputDTO;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoUseCaseInterface;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Domain\Exception\SituacaoBloqueadaException;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoResumidaResponseDTO;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

final class AtualizarSituacaoController {
    public function __construct(
        private readonly AtualizarSituacaoUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Put(path: '/ordens-servico/{id}/em-diagnostico', operationId: 'atualizarParaEmDiagnostico', summary: 'Atualizar para em diagnóstico', tags: ['Ordens de Serviço - Situação'])]
    #[OA\Put(path: '/ordens-servico/{id}/aguardando-aprovacao', operationId: 'atualizarParaAguardandoAprovacao', summary: 'Atualizar para aguardando aprovação', tags: ['Ordens de Serviço - Situação'])]
    #[OA\Put(path: '/ordens-servico/{id}/em-execucao', operationId: 'atualizarParaEmExecucao', summary: 'Atualizar para em execução', tags: ['Ordens de Serviço - Situação'])]
    #[OA\Put(path: '/ordens-servico/{id}/finalizada', operationId: 'atualizarParaFinalizada', summary: 'Atualizar para finalizada', tags: ['Ordens de Serviço - Situação'])]
    #[OA\Put(path: '/ordens-servico/{id}/entregue', operationId: 'atualizarParaEntregue', summary: 'Atualizar para entregue', tags: ['Ordens de Serviço - Situação'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Situação atualizada', content: new OA\JsonContent(ref: OrdemServicoResumidaResponseDTO::class))]
    #[OA\Response(response: 404, description: 'Não encontrada')]
    #[OA\Response(response: 409, description: 'Transição não permitida')]
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

    public function alterarSituacao(int $id, SituacaoOrdemServicoEnum $novaSituacao, ResponseInterface $response): ResponseInterface {
        try {
            $os = $this->useCase->executar(new AtualizarSituacaoInputDTO($id, $novaSituacao));

            return $this->presenter->success(
                $response,
                OrdemServicoResumidaResponseDTO::fromEntity($os),
                HttpStatusCodeEnum::Ok,
            );
        } catch (OrdemServicoNaoEncontradaException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        } catch (SituacaoBloqueadaException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::Conflict);
        }
    }
}

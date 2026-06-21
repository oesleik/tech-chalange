<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use App\OrdemServico\Contract\OrdemServicoResumidaResponse;
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
    content: new OA\JsonContent(ref: '#/components/schemas/OrdemServicoResponse')
)]
#[OA\Response(response: 400, description: 'ID da Ordem de Serviço inválido')]
#[OA\Response(response: 404, description: 'Ordem de Serviço não encontrada')]
#[OA\Response(response: 409, description: 'Situação atual da Ordem de Serviço não permite aprovação')]
class AtualizarSituacaoEmailController {
    public function __construct(
        private ContractResolver $contractResolver,
        private OrdemServicoService $service,
    ) {}

    public function atualizarParaAprovada(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->alterarSituacaoEmail($request, $response, SituacaoOrdemServicoEnum::APROVADA);
    }

    public function atualizarParaRejeitada(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->alterarSituacaoEmail($request, $response, SituacaoOrdemServicoEnum::REJEITADA);
    }

    private function alterarSituacaoEmail(ServerRequestInterface $request, ResponseInterface $response, SituacaoOrdemServicoEnum $novaSituacao) {
        try {
            $claims = $request->getAttribute('jwt_claims', []);
            $idOrdemServico = $claims['id_ordem_servico'] ?? null;

            if (!is_int($idOrdemServico) && !ctype_digit((string) $idOrdemServico)) {
                return $this->erro($response, 'Não foi possível identificar a Ordem de Serviço.', 400);
            }

            $idOrdemServico = intval($idOrdemServico);

            $ordemServico = $this->service->obterOrdemServicoPorId($idOrdemServico);
            if (!$ordemServico) {
                return $this->erro($response, 'Ordem de Serviço não encontrada.', 404);
            }

            $situacaoAtual = SituacaoOrdemServicoEnum::from($ordemServico->getSituacao()->value);

            if (!$situacaoAtual->podeAlterarSituacao(SituacaoOrdemServicoEnum::APROVADA)) {
                return $this->erro(
                    $response,
                    "Não é possível aprovar uma Ordem de Serviço que está '{$situacaoAtual->getFormattedValue()}'.",
                    409
                );
            }

            $ordemServicoAtualizada = $this->service->atualizarSituacao(
                $ordemServico,
                $novaSituacao
            );

            $output = OrdemServicoResumidaResponse::fromModel($ordemServicoAtualizada);

            $response->getBody()->write($this->contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            return $this->erro($response, $e->getMessage(), 400);
        }
    }

    private function erro(ResponseInterface $response, string $mensagem, int $status): ResponseInterface {
        $response->getBody()->write(json_encode(['erro' => $mensagem]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}

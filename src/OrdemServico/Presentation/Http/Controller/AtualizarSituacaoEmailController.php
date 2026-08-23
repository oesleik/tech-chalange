<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

#[OA\Get(path: '/email/ordens-servico/aprovada', operationId: 'atualizarParaAprovada', summary: 'Atualizar ordem para aprovada via e-mail', tags: ['Ordens de Serviço - Situação'], security: [])]
#[OA\Get(path: '/email/ordens-servico/rejeitada', operationId: 'atualizarParaRejeitada', summary: 'Atualizar ordem para rejeitada via e-mail', tags: ['Ordens de Serviço - Situação'], security: [])]
#[OA\Response(response: 200, description: 'Situação atualizada')]
#[OA\Response(response: 404, description: 'Não encontrada')]
#[OA\Response(response: 409, description: 'Transição não permitida')]
#[OA\Response(response: 422, description: 'OS não identificada')]
final class AtualizarSituacaoEmailController {
    public function __construct(
        private readonly AtualizarSituacaoController $attSituacaoController,
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
        SituacaoOrdemServicoEnum $novaSituacao,
    ): ResponseInterface {
        $claims          = $request->getAttribute('jwt_claims', []);
        $idOrdemServico  = $claims['id_ordem_servico'] ?? null;

        if (!is_int($idOrdemServico) && !ctype_digit((string) $idOrdemServico)) {
            return $response->withStatus(422, 'Ordem de serviço não identificada');
        }

        return $this->attSituacaoController->alterarSituacao((int) $idOrdemServico, $novaSituacao, $response);
    }
}

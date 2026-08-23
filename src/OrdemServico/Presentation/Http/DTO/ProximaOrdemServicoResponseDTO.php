<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\Core\Config\AppConfig;
use App\Core\Contract\ApiLinkSchema;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoOutputDTO;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class ProximaOrdemServicoResponseDTO {
    /** @param ApiLinkSchema[] $links */
    public function __construct(
        #[OA\Property(enum: ['realizar_diagnostico', 'executar_servicos'])]
        public readonly string $tipo_servico,
        #[OA\Property]
        public readonly OrdemServicoCompletaResponseDTO $ordem_servico,
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/ApiLinkSchema'))]
        public readonly array $links,
    ) {}

    public static function fromOutputDTO(ObterOrdemServicoOutputDTO $output, AppConfig $appConfig): self {
        $os      = $output->ordemServico;
        $baseUrl = $appConfig->getBaseUrl();
        $id      = $os->id();

        $linksExecucao = [
            new ApiLinkSchema('adicionar_pecas', "{$baseUrl}ordens-servico/{$id}/pecas", 'POST'),
            new ApiLinkSchema('adicionar_servicos', "{$baseUrl}ordens-servico/{$id}/servicos", 'POST'),
            new ApiLinkSchema('atualizar_pecas', "{$baseUrl}ordens-servico/{$id}/pecas", 'PUT'),
            new ApiLinkSchema('atualizar_servicos', "{$baseUrl}ordens-servico/{$id}/servicos", 'PUT'),
        ];

        if ($os->situacao() === SituacaoOrdemServicoEnum::RECEBIDA) {
            return new self(
                tipo_servico: 'realizar_diagnostico',
                ordem_servico: OrdemServicoCompletaResponseDTO::fromOutputDTO($output),
                links: [
                    new ApiLinkSchema('marcar_em_diagnostico', "{$baseUrl}ordens-servico/{$id}/em_diagnostico", 'PUT'),
                    new ApiLinkSchema('enviar_para_aprovacao', "{$baseUrl}ordens-servico/{$id}/aguardando_aprovacao", 'PUT'),
                    ...$linksExecucao,
                ],
            );
        }

        return new self(
            tipo_servico: 'executar_servicos',
            ordem_servico: OrdemServicoCompletaResponseDTO::fromOutputDTO($output),
            links: [
                new ApiLinkSchema('marcar_em_execucao', "{$baseUrl}ordens-servico/{$id}/em_execucao", 'PUT'),
                new ApiLinkSchema('marcar_finalizada', "{$baseUrl}ordens-servico/{$id}/finalizada", 'PUT'),
                ...$linksExecucao,
            ],
        );
    }
}

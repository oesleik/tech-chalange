<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Config\AppConfig;
use App\Core\Contract\AbstractContract;
use App\Core\Contract\ApiLinkSchema;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ProximaOrdemServicoResponse extends AbstractContract {
    /** @param ApiLinkSchema[] $links */
    public function __construct(
        #[OA\Property(enum: ["realizar_diagnostico", "executar_servicos"])]
        public string $tipo_servico,
        #[OA\Property]
        public OrdemServicoResponse $ordem_servico,
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/ApiLinkSchema'))]
        public array $links
    ) {}

    public static function fromModel(OrdemServicoModel $model, AppConfig $appConfig): self {
        $baseUrl = $appConfig->getBaseUrl();
        $idOS = $model->getId();

        $linksExecucao = [
            // new ApiLinkSchema("adicionar_pecas", "", "POST"),
            // new ApiLinkSchema("adicionar_servicos", "", "POST"),
            // new ApiLinkSchema("atualizar_pecas", "", "POST"),
            // new ApiLinkSchema("atualizar_servicos", "", "POST"),
        ];

        if ($model->getSituacao() == SituacaoOrdemServicoEnum::RECEBIDA) {
            return new self(
                tipo_servico: "realizar_diagnostico",
                ordem_servico: OrdemServicoResponse::fromModel($model),
                links: [
                    new ApiLinkSchema("marcar_em_diagnostico", $baseUrl . "ordens-servico/$idOS/em_diagnostico", "PUT"),
                    new ApiLinkSchema("enviar_para_aprovacao", $baseUrl . "ordens-servico/$idOS/aguardando_aprovacao", "PUT"),
                    ...$linksExecucao,
                ]
            );
        } else {
            return new self(
                tipo_servico: "executar_servicos",
                ordem_servico: OrdemServicoResponse::fromModel($model),
                links: [
                    new ApiLinkSchema("marcar_em_execucao", $baseUrl . "ordens-servico/$idOS/em_execucao", "PUT"),
                    new ApiLinkSchema("marcar_finalizada", $baseUrl . "ordens-servico/$idOS/finalizada", "PUT"),
                    ...$linksExecucao,
                ]
            );
        }
    }
}

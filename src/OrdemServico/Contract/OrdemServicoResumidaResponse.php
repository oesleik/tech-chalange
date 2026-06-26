<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class OrdemServicoResumidaResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 123)]
        public int $id,
        #[OA\Property(example: 456)]
        public int $id_cliente,
        #[OA\Property(example: 789)]
        public int $id_veiculo,
        #[OA\Property(enum: SituacaoOrdemServicoEnum::class)]
        public string $situacao,
        #[OA\Property(example: 500.00)]
        public float $valor_total,
        #[OA\Property(format: 'date-time', example: '2026-06-14 10:30:00')]
        public string $data_solicitacao,
        #[OA\Property(format: 'date-time', example: '2026-06-14 11:00:00', nullable: true)]
        public ?string $data_aprovacao,
    ) {}

    public static function fromModel(OrdemServicoModel $model): self {
        return new self(
            id: $model->getId(),
            id_cliente: $model->getIdCliente(),
            id_veiculo: $model->getIdVeiculo(),
            situacao: $model->getSituacao()->value,
            valor_total: $model->getValorTotal(),
            data_solicitacao: $model->getDataSolicitacao()->format('Y-m-d H:i:s'),
            data_aprovacao: $model->getDataAprovacao()?->format('Y-m-d H:i:s'),
        );
    }
}

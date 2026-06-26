<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use App\OrdemServico\Model\ServicoOrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class OrdemServicoCompletaResponse extends AbstractContract {
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
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/PecaOrdemServicoResponse'))]
        public array $pecas,
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/ServicoOrdemServicoResponse'))]
        public array $servicos,
    ) {}

    /**
     * @param PecaOrdemServicoModel[] $pecas
     * @param ServicoOrdemServicoModel[] $servicos
     */
    public static function fromModel(OrdemServicoModel $model, array $pecas, array $servicos): self {
        return new self(
            id: $model->getId(),
            id_cliente: $model->getIdCliente(),
            id_veiculo: $model->getIdVeiculo(),
            situacao: $model->getSituacao()->value,
            valor_total: $model->getValorTotal(),
            data_solicitacao: $model->getDataSolicitacao()->format('Y-m-d H:i:s'),
            data_aprovacao: $model->getDataAprovacao()?->format('Y-m-d H:i:s'),
            pecas: array_map(fn(PecaOrdemServicoModel $peca) => PecaOrdemServicoResponse::fromModel($peca), $pecas),
            servicos: array_map(fn(ServicoOrdemServicoModel $servico) => ServicoOrdemServicoResponse::fromModel($servico), $servicos),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;
use App\Core\Contract\AbstractContract;


use App\OrdemServico\Model\OrdemServicoModel;

readonly class OrdemServicoResponse extends AbstractContract
{
    public function __construct(
        public int $id,
        public int $id_cliente,
        public int $id_veiculo,
        public string $situacao,
        public ?float $valor_total,
        public string $data_solicitacao,
        public ?string $data_aprovacao,
    ) {}

    public static function fromModel(OrdemServicoModel $model): self
    {
        return new self(
            id: $model->getId(),
            id_cliente: $model->getIdCliente(),
            id_veiculo: $model->getIdVeiculo(),
            situacao: $model->getSituacao()->getValue(),
            valor_total: $model->getValorTotal()?->getValue(),
            data_solicitacao: $model->getDataSolicitacao()->format('Y-m-d H:i:s'),
            data_aprovacao: $model->getDataAprovacao()?->format('Y-m-d H:i:s'),
        );
    }
}

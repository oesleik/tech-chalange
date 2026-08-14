<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\OrdemServico\Model\ServicoOrdemServicoModel;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ServicoOrdemServicoResponse extends ServicoOrdemServicoRequest {
    public static function fromModel(ServicoOrdemServicoModel $model): self {
        return new self(
            id_servico: $model->getIdServico(),
            quantidade: $model->getQuantidade(),
        );
    }

}

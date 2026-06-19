<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\OrdemServico\Model\PecaOrdemServicoModel;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class PecaOrdemServicoResponse extends PecaOrdemServicoRequest {
    public static function fromModel(PecaOrdemServicoModel $model): self {
        return new self(
            id_peca: $model->getIdPeca(),
            quantidade: $model->getQuantidade(),
        );
    }

}

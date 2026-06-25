<?php

declare(strict_types=1);

namespace App\Estoque\Contract;

use App\Core\Contract\AbstractContract;
use App\Estoque\Model\EstoquePecaModel;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class EstoquePecaResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 123)]
        public int $id_peca,
        #[OA\Property(example: 10)]
        public int $estoque_atual
    ) {}

    public static function fromLancamentoModel(EstoquePecaModel $lancamento): self {
        return new self(
            id_peca: $lancamento->getIdPeca(),
            estoque_atual: $lancamento->getEstoqueAtual(),
        );
    }

}

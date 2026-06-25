<?php

declare(strict_types=1);

namespace App\Estoque\Contract;

use App\Core\Contract\AbstractContract;
use App\Estoque\Model\LancamentoEstoqueModel;
use App\Estoque\Model\TipoLancamentoEstoqueEnum;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class LancamentoEstoqueResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 456)]
        public int $id,
        #[OA\Property(example: 123)]
        public int $id_peca,
        #[OA\Property(example: 1)]
        public int $quantidade,
        #[OA\Property(example: "baixa")]
        public TipoLancamentoEstoqueEnum $tipo_lancamento,
    ) {}

    public static function fromLancamentoModel(LancamentoEstoqueModel $lancamento): self {
        return new self(
            id: $lancamento->getId(),
            id_peca: $lancamento->getIdPeca(),
            quantidade: $lancamento->getQuantidade(),
            tipo_lancamento: $lancamento->getTipoLancamento(),
        );
    }

}

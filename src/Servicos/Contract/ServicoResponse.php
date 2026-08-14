<?php

declare(strict_types=1);

namespace App\Servicos\Contract;

use App\Servicos\Model\ServicoModel;
use App\Core\Contract\AbstractContract;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ServicoResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 1)]
        public int $id,
        #[OA\Property(example: "Troca de óleo")]
        public string $descricao,
        #[OA\Property(example: 49.90)]
        public float $valor_unitario,
    ) {}

    public static function fromServicoModel(ServicoModel $servico): self {
        return new self(
            id: $servico->getId(),
            descricao: $servico->getDescricao(),
            valor_unitario: $servico->getValorUnitario(),
        );
    }
}

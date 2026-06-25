<?php

declare(strict_types=1);

namespace App\Peca\Contract;

use App\Peca\Model\PecaModel;
use App\Core\Contract\AbstractContract;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class PecaResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 1)]
        public int $id,
        #[OA\Property(example: "Filtro de óleo")]
        public string $descricao,
        #[OA\Property(example: "49,90")]
        public string $valor_unitario,
    ) {}

    public static function fromPecaModel(PecaModel $peca): self {
        return new self(
            id: $peca->getId(),
            descricao: $peca->getDescricao(),
            valor_unitario: number_format($peca->getValorUnitario(), 2, ',', '.'),
        );
    }
}

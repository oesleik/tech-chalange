<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\DTO;

use App\Peca\Domain\Entity\Peca;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class PecaResponseDTO {
    public function __construct(
        #[OA\Property(example: 1)]
        public readonly int $id,
        #[OA\Property(example: "Filtro de óleo")]
        public readonly string $descricao,
        #[OA\Property(example: "49,90")]
        public readonly float $valor_unitario,
    ) {}

    public static function fromEntity(Peca $peca): self {
        return new self(
            id: (int) $peca->id(),
            descricao: $peca->descricao(),
            valor_unitario: $peca->valorUnitario()->getValue(),
        );
    }
}

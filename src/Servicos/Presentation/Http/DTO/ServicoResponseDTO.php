<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\DTO;

use App\Servicos\Domain\Entity\Servico;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class ServicoResponseDTO {
    public function __construct(
        #[OA\Property(example: 1)]
        public readonly int $id,
        #[OA\Property(example: "Troca de óleo")]
        public readonly string $descricao,
        #[OA\Property(example: 49.90)]
        public readonly float $valor_unitario,
    ) {}

    public static function fromEntity(Servico $servico): self {
        return new self(
            id: (int) $servico->id(),
            descricao: $servico->descricao(),
            valor_unitario: $servico->valorUnitario()->getValue(),
        );
    }
}

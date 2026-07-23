<?php

declare(strict_types=1);

namespace App\Estoque\Presentation\Http\DTO;

use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class LancamentoResponseDTO {
    public function __construct(
        #[OA\Property(example: 456)]
        public readonly int $id,
        #[OA\Property(example: 123)]
        public readonly int $id_peca,
        #[OA\Property(example: 10)]
        public readonly int $quantidade,
        #[OA\Property(example: 'entrada')]
        public readonly string $tipo_lancamento,
    ) {}

    public static function fromEntity(LancamentoEstoque $lancamento): self {
        return new self(
            id: (int) $lancamento->id(),
            id_peca: $lancamento->pecaId(),
            quantidade: $lancamento->quantidade(),
            tipo_lancamento: $lancamento->tipo()->value,
        );
    }
}

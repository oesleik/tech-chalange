<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class PecaOrdemServicoResponseDTO {
    public function __construct(
        #[OA\Property(example: 123)]
        public readonly int $id_peca,
        #[OA\Property(example: 2)]
        public readonly int $quantidade,
    ) {}

    public static function fromEntity(PecaOrdemServico $peca): self {
        return new self(id_peca: $peca->idPeca(), quantidade: $peca->quantidade());
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class ServicoOrdemServicoResponseDTO {
    public function __construct(
        #[OA\Property(example: 123)]
        public readonly int $id_servico,
        #[OA\Property(example: 2)]
        public readonly int $quantidade,
    ) {}

    public static function fromEntity(ServicoOrdemServico $servico): self {
        return new self(id_servico: $servico->idServico(), quantidade: $servico->quantidade());
    }
}

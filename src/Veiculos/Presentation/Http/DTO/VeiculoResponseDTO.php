<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\DTO;

use App\Veiculos\Domain\Entity\Veiculo;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class VeiculoResponseDTO {
    public function __construct(
        #[OA\Property(
            example: 10
        )]
        public readonly int $id,
        #[OA\Property(
            example: 'ABC1D23'
        )]
        public readonly string $placa,
        #[OA\Property(
            example: 'Toyota'
        )]
        public readonly string $marca,
        #[OA\Property(
            example: 'Corolla'
        )]
        public readonly string $modelo,
    ) {}

    public static function fromEntity(Veiculo $veiculo): self {
        return new self(
            id: $veiculo->id(),
            placa: $veiculo->placa()->getFormattedValue(),
            marca: $veiculo->marca(),
            modelo: $veiculo->modelo(),
        );
    }
}

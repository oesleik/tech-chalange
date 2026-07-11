<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\DTO;

use App\Veiculos\Domain\Entity\Veiculo;

final class VeiculoResponseDTO {
    public function __construct(
        public readonly int $id,
        public readonly string $placa,
        public readonly string $marca,
        public readonly string $modelo,
    ) {}

    public static function fromEntity(Veiculo $veiculo): self {
        return new self(
            id: $veiculo->id(),
            placa: $veiculo->placa(),
            marca: $veiculo->marca(),
            modelo: $veiculo->modelo(),
        );
    }
}

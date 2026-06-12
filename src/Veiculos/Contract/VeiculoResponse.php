<?php

declare(strict_types=1);

namespace App\Veiculos\Contract;

use App\Veiculos\Model\VeiculoModel;
use App\Core\Contract\AbstractContract;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class VeiculoResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 1)]
        public int $id,
        #[OA\Property(example: "ABC-1234")]
        public string $placa,
        #[OA\Property(example: "Toyota")]
        public string $marca,
        #[OA\Property(example: "Corolla")]
        public string $modelo,
    ) {}

    public static function fromVeiculoModel(VeiculoModel $veiculo): self {
        return new self(
            id: $veiculo->getId(),
            placa: $veiculo->getPlaca(),
            marca: $veiculo->getMarca(),
            modelo: $veiculo->getModelo(),
        );
    }
}

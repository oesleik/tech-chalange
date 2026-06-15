<?php

declare(strict_types=1);

namespace App\Veiculos\Contract;

use App\Veiculos\Model\VeiculoModel;
use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class EditarVeiculoRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: "ABC-1234")]
        public ?string $placa,
        #[OA\Property(example: "Toyota")]
        public ?string $marca,
        #[OA\Property(example: "Corolla")]
        public ?string $modelo,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'placa' => [
                new Assert\Optional(
                    new Assert\Regex(
                        pattern: '/^[A-Z]{3}-?\d{4}$|^[A-Z]{3}\d[A-Z]\d{2}$/',
                        message: 'A placa deve estar no formato ABC-1234 ou ABC1D23 (Mercosul)'
                    ),
                ),
            ],
            'marca' => [
                new Assert\Optional(
                    new Assert\Type('string'),
                ),
            ],
            'modelo' => [
                new Assert\Optional(
                    new Assert\Type('string'),
                ),
            ],
        ]);
    }

    public function toVeiculoModel(): VeiculoModel {
        return new VeiculoModel(
            id: 0,
            placa: $this->placa,
            marca: $this->marca,
            modelo: $this->modelo,
        );
    }
}

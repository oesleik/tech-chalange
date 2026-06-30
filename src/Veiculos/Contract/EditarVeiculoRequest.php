<?php

declare(strict_types=1);

namespace App\Veiculos\Contract;

use App\Core\Contract\AbstractContract;
use App\Veiculos\Validator\Placa;
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
                    new Placa(),
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

}

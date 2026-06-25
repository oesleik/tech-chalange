<?php

declare(strict_types=1);

namespace App\Peca\Contract;

use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class EditarPecaRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: "Filtro de óleo")]
        public ?string $descricao,
        #[OA\Property(example: 49.90)]
        public ?float $valor_unitario,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'descricao' => [
                new Assert\Optional(
                    new Assert\Type('string'),
                ),
            ],
            'valor_unitario' => [
                new Assert\Optional([
                    new Assert\Type('float'),
                    new Assert\PositiveOrZero(),
                ]),
            ],
        ]);
    }

}

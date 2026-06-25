<?php

declare(strict_types=1);

namespace App\Estoque\Contract;

use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class LancamentoEstoqueRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 123)]
        public int $id_peca,
        #[OA\Property(example: 1)]
        public int $quantidade,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'id_peca'    => [
                new Assert\NotBlank(message: 'O campo id_peca é obrigatório.'),
                new Assert\Positive(message: 'O id_peca deve ser um número positivo.'),
            ],
            'quantidade' => [
                new Assert\NotBlank(message: 'O campo quantidade é obrigatório.'),
                new Assert\Positive(message: 'A quantidade deve ser maior que zero.'),
            ],
        ]);
    }
}

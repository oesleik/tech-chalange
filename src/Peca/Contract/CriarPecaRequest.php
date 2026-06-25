<?php

declare(strict_types=1);

namespace App\Peca\Contract;

use App\Peca\Model\PecaModel;
use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class CriarPecaRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: "Filtro de óleo")]
        public string $descricao,
        #[OA\Property(example: 49.90, type: "float")]
        public float|int $valor_unitario,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'descricao' => [
                new Assert\NotBlank(),
                new Assert\Type('string'),
            ],
            'valor_unitario' => [
                new Assert\NotBlank(),
                new Assert\Type('numeric'),
                new Assert\PositiveOrZero(),
            ],
        ]);
    }

    public function toPecaModel(): PecaModel {
        return new PecaModel(
            id: 0,
            descricao: $this->descricao,
            valorUnitario: floatval($this->valor_unitario),
        );
    }
}

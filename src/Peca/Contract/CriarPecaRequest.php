<?php

declare(strict_types=1);

namespace App\Peca\Contract;

use App\Peca\Model\PecaModel;
use App\Peca\ValueObject\DescricaoValue;
use App\Peca\ValueObject\ValorUnitarioValue;
use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class CriarPecaRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: "Filtro de óleo")]
        public string $descricao,
        #[OA\Property(example: 49.90)]
        public float $valor_unitario,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'descricao' => [
                new Assert\NotBlank(),
                new Assert\Type('string'),
            ],
            'valor_unitario' => [
                new Assert\NotBlank(),
                new Assert\Type('float'),
                new Assert\PositiveOrZero(),
            ],
        ]);
    }

    public function toPecaModel(): PecaModel {
        return new PecaModel(
            id: 0,
            descricao: new DescricaoValue($this->descricao),
            valorUnitario: new ValorUnitarioValue($this->valor_unitario),
        );
    }
}
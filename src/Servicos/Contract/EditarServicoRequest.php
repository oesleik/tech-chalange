<?php

declare(strict_types=1);

namespace App\Servicos\Contract;

use App\Servicos\Model\ServicoModel;
use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class EditarServicoRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: "Troca de óleo")]
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
                    new Assert\Type('numeric'),
                    new Assert\PositiveOrZero(),
                ]),
            ],
        ]);
    }

    public function toServicoModel(): ServicoModel {
        return new ServicoModel(
            id: 0,
            descricao: $this->descricao,
            valorUnitario: $this->valor_unitario,
        );
    }
}

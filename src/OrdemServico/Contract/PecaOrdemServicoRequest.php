<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class PecaOrdemServicoRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 123)]
        public int $id_peca,
        #[OA\Property(example: 1)]
        public int $quantidade,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'id_peca' => [
                new Assert\NotBlank(),
                new Assert\Type("integer"),
                new Assert\Positive(),
            ],
            'quantidade' => [
                new Assert\NotBlank(),
                new Assert\Type("integer"),
                new Assert\Positive(),
            ],
        ]);
    }

    public function toPecaOrdemServicoModel(): PecaOrdemServicoModel {
        return new PecaOrdemServicoModel(
            idPeca: $this->id_peca,
            quantidade: $this->quantidade,
            valorUnitario: 0,
        );
    }
}

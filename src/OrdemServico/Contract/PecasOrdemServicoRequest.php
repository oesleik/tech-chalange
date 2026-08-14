<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class PecasOrdemServicoRequest extends AbstractContract {
    /** @param PecaOrdemServicoRequest[] $pecas */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/PecaOrdemServicoRequest'))]
        public array $pecas
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'pecas' => [
                new Assert\Type('array'),
                new Assert\All(PecaOrdemServicoRequest::getConstraints()),
            ],
        ]);
    }

    /** @return PecaOrdemServicoModel[] */
    public function toPecasOrdemServicoModelArray(): array {
        return array_map(fn(PecaOrdemServicoRequest $peca) => $peca->toPecaOrdemServicoModel(), $this->pecas);
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\OrdemServico\Model\ServicoOrdemServicoModel;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ServicosOrdemServicoRequest extends AbstractContract {
    /** @param ServicoOrdemServicoRequest[] $servicos */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/ServicoOrdemServicoRequest'))]
        public array $servicos
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'servicos' => [
                new Assert\Type('array'),
                new Assert\All(ServicoOrdemServicoRequest::getConstraints()),
            ],
        ]);
    }

    /** @return ServicoOrdemServicoModel[] */
    public function toServicosOrdemServicoModelArray(): array {
        return array_map(fn(ServicoOrdemServicoRequest $servico) => $servico->toServicoOrdemServicoModel(), $this->servicos);
    }
}

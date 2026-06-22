<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\OrdemServico\ValueObject\FiltroOrdemServico;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class FiltrarOrdensServicoRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(description: 'Situação da Ordem de Serviço', example: 'Aprovada', nullable: true)]
        public ?string $situacao = null,
        #[OA\Property(description: 'ID do Cliente', example: 123, nullable: true)]
        public ?int $id_cliente = null,
        #[OA\Property(description: 'ID do Veículo', example: 456, nullable: true)]
        public ?int $id_veiculo = null,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'situacao' => [
                new Assert\Optional([
                    new Assert\Type('string'),
                ]),
            ],
            'id_cliente' => [
                new Assert\Optional([
                    new Assert\Type('integer'),
                    new Assert\Positive(),
                ]),
            ],
            'id_veiculo' => [
                new Assert\Optional([
                    new Assert\Type('integer'),
                    new Assert\Positive(),
                ]),
            ],
        ]);
    }

    public function toFiltroOrdemServico(): FiltroOrdemServico {
        return FiltroOrdemServico::fromArray([
            'situacao' => $this->situacao,
            'id_cliente' => $this->id_cliente,
            'id_veiculo' => $this->id_veiculo,
        ]);
    }
}

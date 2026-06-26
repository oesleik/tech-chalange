<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\OrdemServico\Model\FiltroOrdemServico;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class OrdensServicoFiltros extends AbstractContract {
    public function __construct(
        #[OA\Property(description: 'Situação da Ordem de Serviço', example: 'Aprovada', nullable: true)]
        public ?SituacaoOrdemServicoEnum $situacao = null,
        #[OA\Property(description: 'ID do Cliente', example: 123, nullable: true)]
        public ?int $id_cliente = null,
        #[OA\Property(description: 'ID do Veículo', example: 456, nullable: true)]
        public ?int $id_veiculo = null,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'situacao' => [
                new Assert\Optional([
                    new Assert\Type(SituacaoOrdemServicoEnum::class),
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

    public function toFiltroModel(): FiltroOrdemServico {
        return new FiltroOrdemServico(
            situacao: $this->situacao,
            idCliente: $this->id_cliente,
            idVeiculo: $this->id_veiculo,
        );
    }
}

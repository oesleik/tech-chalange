<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Domain\Entity\OrdemServico;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class OrdemServicoResumidaResponseDTO {
    public function __construct(
        #[OA\Property(example: 123)]
        public readonly int $id,
        #[OA\Property(example: 456)]
        public readonly int $id_cliente,
        #[OA\Property(example: 789)]
        public readonly int $id_veiculo,
        #[OA\Property(example: 'Recebida')]
        public readonly string $situacao,
        #[OA\Property(example: 500.00)]
        public readonly float $valor_total,
        #[OA\Property(format: 'date-time', example: '2026-06-14 10:30:00')]
        public readonly string $data_solicitacao,
        #[OA\Property(format: 'date-time', example: '2026-06-14 11:00:00', nullable: true)]
        public readonly ?string $data_aprovacao,
    ) {}

    public static function fromEntity(OrdemServico $os): self {
        return new self(
            id: $os->id(),
            id_cliente: $os->idCliente(),
            id_veiculo: $os->idVeiculo(),
            situacao: $os->situacao()->value,
            valor_total: $os->valorTotal(),
            data_solicitacao: $os->dataSolicitacao()->format('Y-m-d H:i:s'),
            data_aprovacao: $os->dataAprovacao()?->format('Y-m-d H:i:s'),
        );
    }
}

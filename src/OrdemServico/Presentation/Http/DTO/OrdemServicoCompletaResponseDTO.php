<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoOutputDTO;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class OrdemServicoCompletaResponseDTO {
    /**
     * @param PecaOrdemServicoResponseDTO[] $pecas
     * @param ServicoOrdemServicoResponseDTO[] $servicos
     */
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
        #[OA\Property(format: 'date-time')]
        public readonly string $data_solicitacao,
        #[OA\Property(format: 'date-time', nullable: true)]
        public readonly ?string $data_aprovacao,
        #[OA\Property(type: 'array', items: new OA\Items(ref: PecaOrdemServicoResponseDTO::class))]
        public readonly array $pecas,
        #[OA\Property(type: 'array', items: new OA\Items(ref: ServicoOrdemServicoResponseDTO::class))]
        public readonly array $servicos,
    ) {}

    public static function fromOutputDTO(ObterOrdemServicoOutputDTO $output): self {
        $os = $output->ordemServico;

        return new self(
            id: $os->id(),
            id_cliente: $os->idCliente(),
            id_veiculo: $os->idVeiculo(),
            situacao: $os->situacao()->value,
            valor_total: $os->valorTotal(),
            data_solicitacao: $os->dataSolicitacao()->format('Y-m-d H:i:s'),
            data_aprovacao: $os->dataAprovacao()?->format('Y-m-d H:i:s'),
            pecas: array_map(PecaOrdemServicoResponseDTO::fromEntity(...), $output->pecas),
            servicos: array_map(ServicoOrdemServicoResponseDTO::fromEntity(...), $output->servicos),
        );
    }
}

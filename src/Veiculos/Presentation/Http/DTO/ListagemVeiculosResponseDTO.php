<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\DTO;

use App\Core\Presentation\Http\DTO\PaginacaoDTO;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoOutputDTO;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class ListagemVeiculosResponseDTO {
    /**
     * @param VeiculoResponseDTO[] $dados
     */
    public function __construct(
        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: VeiculoResponseDTO::class)
        )]
        public readonly array $dados,
        #[OA\Property(ref: PaginacaoDTO::class)]
        public readonly PaginacaoDTO $paginacao,
    ) {}

    public static function fromOutputDTO(ListarVeiculoOutputDTO $output): self {
        $dados = array_map(
            static fn($veiculo) => VeiculoResponseDTO::fromEntity($veiculo),
            $output->veiculos,
        );

        return new self(
            dados: $dados,
            paginacao: PaginacaoDTO::fromTotais($output->pagina, $output->porPagina, $output->total),
        );
    }
}

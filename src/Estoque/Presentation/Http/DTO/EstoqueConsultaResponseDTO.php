<?php

declare(strict_types=1);

namespace App\Estoque\Presentation\Http\DTO;

use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaOutputDTO;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class EstoqueConsultaResponseDTO
{
    public function __construct(
        #[OA\Property(example: 123)]
        public readonly int $id_peca,
        #[OA\Property(example: 10)]
        public readonly int $estoque_atual,
    ) {}

    public static function fromOutputDTO(ConsultarEstoquePorPecaOutputDTO $output): self
    {
        return new self(
            id_peca: $output->pecaId,
            estoque_atual: $output->estoqueAtual,
        );
    }
}
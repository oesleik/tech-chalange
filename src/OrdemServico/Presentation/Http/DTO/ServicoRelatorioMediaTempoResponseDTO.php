<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\ServicoRelatorioDTO;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class ServicoRelatorioMediaTempoResponseDTO {
    public function __construct(
        #[OA\Property(example: 123)]
        public readonly int $id_servico,
        #[OA\Property(example: 'Troca de óleo')]
        public readonly string $descricao,
        #[OA\Property(example: 49.90)]
        public readonly float $valor_unitario,
        #[OA\Property(example: 1.5)]
        public readonly float $media_tempo,
        #[OA\Property(example: 3)]
        public readonly int $quantidade_execucoes,
        #[OA\Property(example: 4.5)]
        public readonly float $total_tempo_executando,
        #[OA\Property(example: 1.1)]
        public readonly float $min_tempo_execucao,
        #[OA\Property(example: 2.2)]
        public readonly float $max_tempo_execucao,
    ) {}

    public static function fromDTO(ServicoRelatorioDTO $dto): self {
        return new self(
            id_servico: $dto->idServico,
            descricao: $dto->descricao,
            valor_unitario: $dto->valorUnitario,
            media_tempo: $dto->mediaTempo,
            quantidade_execucoes: $dto->quantidadeExecucoes,
            total_tempo_executando: $dto->totalTempoExecutando,
            min_tempo_execucao: $dto->minTempoExecucao,
            max_tempo_execucao: $dto->maxTempoExecucao,
        );
    }
}

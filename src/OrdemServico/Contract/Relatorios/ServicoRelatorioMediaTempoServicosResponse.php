<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract\Relatorios;

use App\Core\Contract\AbstractContract;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ServicoRelatorioMediaTempoServicosResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 123)]
        public int $id_servico,
        #[OA\Property(example: "Troca de óleo")]
        public string $descricao,
        #[OA\Property(example: 49.90)]
        public float $valor_unitario,
        #[OA\Property(example: 1.5)]
        public float $media_tempo,
        #[OA\Property(example: 3)]
        public int $quantidade_execucoes,
        #[OA\Property(example: 4.5)]
        public float $total_tempo_executando,
        #[OA\Property(example: 1.1)]
        public float $min_tempo_execucao,
        #[OA\Property(example: 2.2)]
        public float $max_tempo_execucao,
    ) {}

}

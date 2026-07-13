<?php

declare(strict_types=1);

namespace App\Core\Presentation\Http\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema]
final class PaginacaoDTO {
    public function __construct(
        #[OA\Property(example: 1)]
        public readonly int $pagina,
        #[OA\Property(example: 20)]
        public readonly int $porPagina,
        #[OA\Property(example: 154)]
        public readonly int $total,
        #[OA\Property(example: 8)]
        public readonly int $totalPaginas,
    ) {}

    public static function fromTotais(int $pagina, int $porPagina, int $total): self {
        return new self(
            pagina: $pagina,
            porPagina: $porPagina,
            total: $total,
            totalPaginas: (int) ceil($total / max(1, $porPagina)),
        );
    }
}

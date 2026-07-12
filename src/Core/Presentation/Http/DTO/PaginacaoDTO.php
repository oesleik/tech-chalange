<?php

declare(strict_types=1);

namespace App\Core\Presentation\Http\DTO;

final class PaginacaoDTO {
    public function __construct(
        public readonly int $pagina,
        public readonly int $porPagina,
        public readonly int $total,
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

<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema]
final class ListarServicosResponseDTO {
    /** @param ServicoResponseDTO[] $servicos */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: ServicoResponseDTO::class))]
        public readonly array $servicos,
    ) {}
}

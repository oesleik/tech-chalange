<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema]
final class ListarPecasResponseDTO {
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/PecaResponseDTO'))]
        public readonly array $pecas,
    ) {}
}
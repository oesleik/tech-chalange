<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\DTO;

use OpenApi\Attributes as OA;

/**
 * Existe apenas para gerar o schema OpenAPI do corpo da requisição.
 * O parse/validação real acontece em CriarPecaMapper.
 */
#[OA\Schema]
final class CriarPecaRequestBody {
    public function __construct(
        #[OA\Property(example: "Filtro de óleo")]
        public string $descricao,
        #[OA\Property(example: 49.90, type: "float")]
        public float|int $valor_unitario,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\DTO;

use OpenApi\Attributes as OA;

/**
 * Existe apenas para gerar o schema OpenAPI do corpo da requisição.
 * O parse/validação real acontece em CriarServicoMapper.
 */
#[OA\Schema]
final class CriarServicoRequestBody {
    public function __construct(
        #[OA\Property(example: "Troca de óleo")]
        public string $descricao,
        #[OA\Property(example: 49.90, type: "float")]
        public float|int $valor_unitario,
    ) {}
}

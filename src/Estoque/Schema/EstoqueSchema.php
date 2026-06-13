<?php

declare(strict_types=1);

namespace App\Estoque\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EntradaEstoqueRequest',
    properties: [
        new OA\Property(property: 'id_peca',    type: 'integer', example: 1),
        new OA\Property(property: 'quantidade', type: 'integer', example: 10),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'EntradaEstoqueResponse',
    properties: [
        new OA\Property(property: 'id',              type: 'integer', example: 1),
        new OA\Property(property: 'id_peca',         type: 'integer', example: 1),
        new OA\Property(property: 'peca',            type: 'string',  example: 'Filtro de óleo'),
        new OA\Property(property: 'quantidade',      type: 'integer', example: 10),
        new OA\Property(property: 'tipo_lancamento', type: 'string',  example: 'entrada'),
    ],
    type: 'object'
)]
final class EstoqueSchema {}
<?php

declare(strict_types=1);

namespace App\Estoque\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EntradaEstoqueRequest',
    properties: [
        new OA\Property(property: 'id_peca', type: 'integer', example: 1),
        new OA\Property(property: 'quantidade', type: 'integer', example: 10),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'EntradaEstoqueResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'id_peca', type: 'integer', example: 1),
        new OA\Property(property: 'peca', type: 'string', example: 'Filtro de óleo'),
        new OA\Property(property: 'quantidade', type: 'integer', example: 10),
        new OA\Property(property: 'tipo_lancamento', type: 'string', example: 'entrada'),
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'ConsultaEstoqueResponse',
    properties: [
        new OA\Property(property: 'id_peca', type: 'integer', example: 1),
        new OA\Property(property: 'descricao', type: 'string', example: 'Filtro de óleo'),
        new OA\Property(property: 'valor_unitario', type: 'number', example: 29.90),
        new OA\Property(property: 'estoque_atual', type: 'integer', example: 10),
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'BaixaEstoqueRequest',
    properties: [
        new OA\Property(property: 'id_peca', type: 'integer', example: 1),
        new OA\Property(property: 'quantidade', type: 'integer', example: 5),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'BaixaEstoqueResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'id_peca', type: 'integer', example: 1),
        new OA\Property(property: 'peca', type: 'string', example: 'Filtro de óleo'),
        new OA\Property(property: 'quantidade', type: 'integer', example: 5),
        new OA\Property(property: 'tipo_lancamento', type: 'string', example: 'baixa'),
        new OA\Property(property: 'estoque_atual', type: 'integer', example: 5),
    ],
    type: 'object'
)]

final class EstoqueSchema {}

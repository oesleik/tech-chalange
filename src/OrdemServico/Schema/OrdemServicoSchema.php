<?php

declare(strict_types=1);

namespace App\OrdemServico\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CriarOrdemServicoRequest',
    properties: [
        new OA\Property(property: 'id_cliente',  type: 'integer', example: 1),
        new OA\Property(property: 'id_veiculo',  type: 'integer', example: 1),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'OrdemServicoResponse',
    properties: [
        new OA\Property(property: 'id',                  type: 'integer', example: 1),
        new OA\Property(property: 'id_cliente',          type: 'integer', example: 1),
        new OA\Property(property: 'id_veiculo',          type: 'integer', example: 1),
        new OA\Property(property: 'situacao',            type: 'string',  example: 'Recebida'),
        new OA\Property(property: 'valor_total',         type: 'number',  example: 500.00),
        new OA\Property(property: 'data_solicitacao',    type: 'string',  format: 'date-time', example: '2026-06-14 10:30:00'),
        new OA\Property(property: 'data_aprovacao',      type: 'string',  format: 'date-time', example: '2026-06-14 11:00:00', nullable: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'AtualizarSituacaoRequest',
    properties: [
        new OA\Property(
            property: 'situacao',
            type: 'string',
            enum: ['Recebida', 'EmDiagnostico', 'AguardandoAprovacao', 'Aprovada', 'Rejeitada', 'EmExecucao', 'Finalizada', 'Entregue'],
            example: 'EmDiagnostico'
        ),
    ],
    type: 'object'
)]
final class OrdemServicoSchema {}

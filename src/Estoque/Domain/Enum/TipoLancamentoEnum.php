<?php

declare(strict_types=1);

namespace App\Estoque\Domain\Enum;

// enum de domínio — representa se foi uma entrada ou saída de peça
enum TipoLancamentoEnum: string
{
    case ENTRADA = 'entrada';
    case BAIXA   = 'baixa';
}
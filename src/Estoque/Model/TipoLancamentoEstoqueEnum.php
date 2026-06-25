<?php

declare(strict_types=1);

namespace App\Estoque\Model;

enum TipoLancamentoEstoqueEnum: string {
    case ENTRADA = "entrada";
    case BAIXA = "baixa";
}

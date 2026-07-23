<?php

declare(strict_types=1);

namespace App\Estoque\Application\Gateway;

use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;

// o Use Case depende disso — a implementação concreta fica na Infrastructure
interface EstoqueGatewayInterface
{
    public function pecaExiste(int $pecaId): bool;
    public function calcularEstoqueAtual(int $pecaId): int;
    public function inserirLancamento(int $pecaId, int $quantidade, TipoLancamentoEnum $tipo): LancamentoEstoque;
}
<?php

declare(strict_types=1);

namespace App\Estoque\Application\UseCase\RegistrarEntradaEstoque;

use App\Estoque\Domain\Entity\LancamentoEstoque;

interface RegistrarEntradaEstoqueUseCaseInterface
{
    public function executar(RegistrarEntradaEstoqueInputDTO $input): LancamentoEstoque;
}
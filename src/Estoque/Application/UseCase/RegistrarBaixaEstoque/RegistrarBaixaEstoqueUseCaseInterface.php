<?php

declare(strict_types=1);

namespace App\Estoque\Application\UseCase\RegistrarBaixaEstoque;

use App\Estoque\Domain\Entity\LancamentoEstoque;

interface RegistrarBaixaEstoqueUseCaseInterface
{
    public function executar(RegistrarBaixaEstoqueInputDTO $input): LancamentoEstoque;
}
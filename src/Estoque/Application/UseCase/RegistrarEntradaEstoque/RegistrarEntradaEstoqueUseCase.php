<?php

declare(strict_types=1);

namespace App\Estoque\Application\UseCase\RegistrarEntradaEstoque;

use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;

final class RegistrarEntradaEstoqueUseCase implements RegistrarEntradaEstoqueUseCaseInterface {
    public function __construct(private readonly EstoqueGatewayInterface $gateway) {}

    public function executar(RegistrarEntradaEstoqueInputDTO $input): LancamentoEstoque {
        if (!$this->gateway->pecaExiste($input->pecaId)) {
            throw PecaNaoEncontradaException::comId($input->pecaId);
        }

        return $this->gateway->inserirLancamento($input->pecaId, $input->quantidade, TipoLancamentoEnum::ENTRADA);
    }
}

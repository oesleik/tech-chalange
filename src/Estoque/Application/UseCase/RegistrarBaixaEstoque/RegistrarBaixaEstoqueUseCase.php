<?php

declare(strict_types=1);

namespace App\Estoque\Application\UseCase\RegistrarBaixaEstoque;

use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use App\Estoque\Domain\Exception\EstoqueInsuficienteException;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;

final class RegistrarBaixaEstoqueUseCase implements RegistrarBaixaEstoqueUseCaseInterface {
    public function __construct(private readonly EstoqueGatewayInterface $gateway) {}

    public function executar(RegistrarBaixaEstoqueInputDTO $input): LancamentoEstoque {
        if (!$this->gateway->pecaExiste($input->pecaId)) {
            throw PecaNaoEncontradaException::comId($input->pecaId);
        }

        $disponivel = $this->gateway->calcularEstoqueAtual($input->pecaId);

        // regra de negócio: não permite baixar mais do que tem disponível
        if ($input->quantidade > $disponivel) {
            throw EstoqueInsuficienteException::para($input->pecaId, $disponivel, $input->quantidade);
        }

        return $this->gateway->inserirLancamento($input->pecaId, $input->quantidade, TipoLancamentoEnum::BAIXA);
    }
}

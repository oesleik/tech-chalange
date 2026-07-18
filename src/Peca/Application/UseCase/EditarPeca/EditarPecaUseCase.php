<?php

declare(strict_types=1);

namespace App\Peca\Application\UseCase\EditarPeca;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\Exception\PecaNaoEncontradaException;
use App\Peca\Domain\ValueObject\ValorUnitario;

final class EditarPecaUseCase {
    public function __construct(private readonly PecaGatewayInterface $gateway) {}

    public function executar(int $id, EditarPecaInputDTO $input): Peca {
        $peca = $this->gateway->buscarPorId($id)
            ?? throw PecaNaoEncontradaException::comId($id);

        if ($input->descricao !== null) {
            $peca = $peca->comDescricao($input->descricao);
        }
        if ($input->valorUnitario !== null) {
            $peca = $peca->comValorUnitario(new ValorUnitario($input->valorUnitario));
        }

        return $this->gateway->atualizar($peca);
    }
}

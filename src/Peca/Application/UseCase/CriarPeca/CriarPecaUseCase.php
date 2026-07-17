<?php

declare(strict_types=1);

namespace App\Peca\Application\UseCase\CriarPeca;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;

final class CriarPecaUseCase {
    public function __construct(private readonly PecaGatewayInterface $gateway) {}

    public function executar(CriarPecaInputDTO $input): Peca {
        $peca = Peca::criar(
            $input->descricao,
            new ValorUnitario($input->valorUnitario),
        );

        return $this->gateway->inserir($peca);
    }
}
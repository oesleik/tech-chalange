<?php

declare(strict_types=1);

namespace App\Peca\Application\UseCase\ObterPeca;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\Exception\PecaNaoEncontradaException;

final class ObterPecaUseCase {
    public function __construct(private readonly PecaGatewayInterface $gateway) {}

    public function executar(int $id): Peca {
        $peca = $this->gateway->buscarPorId($id);
        if ($peca === null) {
            throw PecaNaoEncontradaException::comId($id);
        }
        return $peca;
    }
}
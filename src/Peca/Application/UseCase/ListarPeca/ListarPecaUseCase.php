<?php

declare(strict_types=1);

namespace App\Peca\Application\UseCase\ListarPeca;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Domain\Entity\Peca;

class ListarPecaUseCase {
    public function __construct(private readonly PecaGatewayInterface $gateway) {}

    /** @return Peca[] */
    public function executar(): array {
        return $this->gateway->listar();
    }
}

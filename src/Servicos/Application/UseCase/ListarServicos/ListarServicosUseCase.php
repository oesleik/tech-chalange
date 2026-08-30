<?php

declare(strict_types=1);

namespace App\Servicos\Application\UseCase\ListarServicos;

use App\Servicos\Application\Gateway\ServicoGatewayInterface;
use App\Servicos\Domain\Entity\Servico;

class ListarServicosUseCase {
    public function __construct(private readonly ServicoGatewayInterface $gateway) {}

    /** @return Servico[] */
    public function executar(): array {
        return $this->gateway->listar();
    }
}

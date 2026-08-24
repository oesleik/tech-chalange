<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\Gateway;

use App\Clientes\Domain\Entity\Cliente;
use App\OrdemServico\Domain\Entity\OrdemServico;

interface EnviarOrcamentoGatewayInterface {
    public function enviar(OrdemServico $ordemServico, Cliente $cliente): void;
}

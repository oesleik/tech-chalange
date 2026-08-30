<?php

declare(strict_types=1);

namespace App\Servicos\Application\UseCase\ObterServico;

use App\Servicos\Application\Gateway\ServicoGatewayInterface;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\Exception\ServicoNaoEncontradoException;

class ObterServicoUseCase {
    public function __construct(private readonly ServicoGatewayInterface $gateway) {}

    public function executar(int $id): Servico {
        $servico = $this->gateway->buscarPorId($id);
        if ($servico === null) {
            throw ServicoNaoEncontradoException::comId($id);
        }
        return $servico;
    }
}

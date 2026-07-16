<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\ObterVeiculo;

use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;

class ObterVeiculoUseCase {
    public function __construct(
        private readonly VeiculoGatewayInterface $gateway,
    ) {}

    public function executar(int $idVeiculo): Veiculo {
        $veiculo = $this->gateway->buscarPorId($idVeiculo);

        if ($veiculo === null) {
            throw VeiculoNaoEncontradoException::comId($idVeiculo);
        }

        return $veiculo;
    }
}

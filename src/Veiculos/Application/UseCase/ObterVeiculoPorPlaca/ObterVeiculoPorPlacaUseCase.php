<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\ObterVeiculoPorPlaca;

use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;

class ObterVeiculoPorPlacaUseCase {
    public function __construct(
        private readonly VeiculoGatewayInterface $gateway,
    ) {}

    public function executar(string $placa): Veiculo {
        $veiculo = $this->gateway->buscarPorPlaca(new Placa($placa));

        if ($veiculo === null) {
            throw VeiculoNaoEncontradoException::comPlaca($placa);
        }

        return $veiculo;
    }
}

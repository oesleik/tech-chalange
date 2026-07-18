<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\CriarVeiculo;

use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;

class CriarVeiculoUseCase {
    public function __construct(
        private readonly VeiculoGatewayInterface $gateway,
    ) {}

    public function executar(Veiculo $veiculo): Veiculo {
        $veiculoExistente = $this->gateway->buscarPorPlaca($veiculo->placa());
        if ($veiculoExistente !== null) {
            throw VeiculoJaCadastradoException::comPlaca($veiculo->placa()->getFormattedValue());
        }

        return $this->gateway->inserir($veiculo);
    }
}

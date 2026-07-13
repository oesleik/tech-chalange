<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\CriarVeiculo;

use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Infrastructure\Persistence\VeiculoGatewayInterface;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;

final class CriarVeiculoUseCase {
    public function __construct(
        private readonly VeiculoGatewayInterface $gateway,
    ) {}

    public function executar(CriarVeiculoInputDTO $input): Veiculo {
        $placa = new Placa($input->placa);

        $veiculoExistente = $this->gateway->buscarPorPlaca($placa);
        if ($veiculoExistente !== null) {
            throw VeiculoJaCadastradoException::comPlaca($placa->getFormattedValue());
        }

        $veiculo = new Veiculo(
            id: null,
            placa: $placa,
            marca: $input->marca,
            modelo: $input->modelo,
        );

        return $this->gateway->inserir($veiculo);
    }
}

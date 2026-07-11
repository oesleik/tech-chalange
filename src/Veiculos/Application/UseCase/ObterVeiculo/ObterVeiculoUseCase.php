<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\ObterVeiculo;

use App\Veiculos\Infrastructure\Persistence\VeiculoGatewayInterface;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;

final class ObterVeiculoUseCase {
    public function __construct(
        private readonly VeiculoGatewayInterface $gateway,
    ) {}

    public function executar(ObterVeiculoInput $input): Veiculo {
        $veiculo = $this->gateway->buscarPorId($input->id);

        if ($veiculo === null) {
            throw VeiculoNaoEncontradoException::comId($input->id);
        }

        return $veiculo;
    }
}

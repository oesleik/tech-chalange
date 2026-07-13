<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\EditarVeiculo;

use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Infrastructure\Persistence\VeiculoGatewayInterface;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;

final class EditarVeiculoUseCase {
    public function __construct(
        private readonly VeiculoGatewayInterface $gateway,
    ) {}

    public function executar(int $id, EditarVeiculoInputDTO $input): Veiculo {
        $veiculo = $this->gateway->buscarPorId($id);
        if ($veiculo === null) {
            throw VeiculoNaoEncontradoException::comId($id);
        }

        $veiculoAtualizado = $this->aplicarAlteracoes($veiculo, $input);

        return $this->gateway->atualizar($veiculoAtualizado);
    }

    private function aplicarAlteracoes(Veiculo $veiculo, EditarVeiculoInputDTO $input): Veiculo {
        if ($input->placa !== null) {
            $novaPlaca = new Placa($input->placa);

            if ($novaPlaca->getValue() !== $veiculo->placa()->getValue()) {
                $this->garantirPlacaDisponivel($novaPlaca);
                $veiculo = $veiculo->comPlaca($novaPlaca);
            }
        }

        if ($input->marca !== null) {
            $veiculo = $veiculo->comMarca($input->marca);
        }

        if ($input->modelo !== null) {
            $veiculo = $veiculo->comModelo($input->modelo);
        }

        return $veiculo;
    }

    private function garantirPlacaDisponivel(Placa $placa): void {
        $veiculoComEssaPlaca = $this->gateway->buscarPorPlaca($placa);
        if ($veiculoComEssaPlaca !== null) {
            throw VeiculoJaCadastradoException::comPlaca($placa->getFormattedValue());
        }
    }
}

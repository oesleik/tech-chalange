<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\ListarVeiculo;

use App\Veiculos\Application\Gateway\FiltroListagemVeiculo;
use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;

final class ListarVeiculoUseCase {
    public function __construct(
        private readonly VeiculoGatewayInterface $gateway,
    ) {}

    public function executar(ListarVeiculoInputDTO $input): ListarVeiculoOutputDTO {
        $filtro = new FiltroListagemVeiculo(
            placa: $input->placa,
            marca: $input->marca,
            modelo: $input->modelo,
            pagina: $input->pagina,
            porPagina: $input->porPagina,
        );

        $total = $this->gateway->contar($filtro);
        $veiculos = $this->gateway->listar($filtro);

        return new ListarVeiculoOutputDTO(
            veiculos: $veiculos,
            total: $total,
            pagina: $input->pagina,
            porPagina: $input->porPagina,
        );
    }
}

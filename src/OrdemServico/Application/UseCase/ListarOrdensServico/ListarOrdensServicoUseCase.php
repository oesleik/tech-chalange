<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\ListarOrdensServico;

use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\ValueObject\FiltroOrdemServico;

final class ListarOrdensServicoUseCase implements ListarOrdensServicoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $gateway,
    ) {}

    public function executar(ListarOrdensServicoInputDTO $input): array {
        return $this->gateway->listar(new FiltroOrdemServico(
            situacao: $input->situacao,
            idCliente: $input->idCliente,
            idVeiculo: $input->idVeiculo,
        ));
    }
}

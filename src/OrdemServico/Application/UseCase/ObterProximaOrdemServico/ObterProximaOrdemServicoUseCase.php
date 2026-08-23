<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\ObterProximaOrdemServico;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoOutputDTO;

final class ObterProximaOrdemServicoUseCase implements ObterProximaOrdemServicoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $gateway,
        private readonly ItensOrdemServicoGatewayInterface $itensGateway,
    ) {}

    public function executar(): ?ObterOrdemServicoOutputDTO {
        $ordemServico = $this->gateway->obterProximaNaFila();

        if ($ordemServico === null) {
            return null;
        }

        return new ObterOrdemServicoOutputDTO(
            ordemServico: $ordemServico,
            pecas: $this->itensGateway->buscarPecasPorOrdemServico($ordemServico->id()),
            servicos: $this->itensGateway->buscarServicosPorOrdemServico($ordemServico->id()),
        );
    }
}

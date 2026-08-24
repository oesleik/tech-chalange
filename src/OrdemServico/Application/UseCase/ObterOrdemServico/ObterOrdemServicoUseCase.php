<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\ObterOrdemServico;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;

final class ObterOrdemServicoUseCase implements ObterOrdemServicoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $gateway,
        private readonly ItensOrdemServicoGatewayInterface $itensGateway,
    ) {}

    public function executar(int $id): ObterOrdemServicoOutputDTO {
        $ordemServico = $this->gateway->buscarPorId($id)
            ?? throw OrdemServicoNaoEncontradaException::comId($id);

        return new ObterOrdemServicoOutputDTO(
            ordemServico: $ordemServico,
            pecas: $this->itensGateway->buscarPecasPorOrdemServico($id),
            servicos: $this->itensGateway->buscarServicosPorOrdemServico($id),
        );
    }
}

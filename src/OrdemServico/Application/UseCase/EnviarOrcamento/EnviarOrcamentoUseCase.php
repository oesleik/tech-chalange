<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\EnviarOrcamento;

use App\Clientes\Application\UseCase\ObterCliente\ObterClienteUseCaseInterface;
use App\OrdemServico\Application\Gateway\EnviarOrcamentoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;

final class EnviarOrcamentoUseCase implements EnviarOrcamentoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $ordemServicoGateway,
        private readonly ObterClienteUseCaseInterface $obterClienteUseCase,
        private readonly EnviarOrcamentoGatewayInterface $enviarOrcamentoGateway,
    ) {}

    public function executar(int $idOrdemServico): void {
        $ordemServico = $this->ordemServicoGateway->buscarPorId($idOrdemServico)
            ?? throw OrdemServicoNaoEncontradaException::comId($idOrdemServico);

        $cliente = $this->obterClienteUseCase->executar($ordemServico->idCliente());

        $this->enviarOrcamentoGateway->enviar($ordemServico, $cliente);
    }
}

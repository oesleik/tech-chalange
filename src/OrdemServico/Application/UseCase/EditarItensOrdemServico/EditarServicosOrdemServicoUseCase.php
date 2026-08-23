<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\EditarItensOrdemServico;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;

final class EditarServicosOrdemServicoUseCase implements EditarServicosOrdemServicoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $ordemServicoGateway,
        private readonly ItensOrdemServicoGatewayInterface $itensGateway,
    ) {}

    public function executar(EditarItensInputDTO $input): void {
        $ordemServico = $this->ordemServicoGateway->buscarPorId($input->idOrdemServico)
            ?? throw OrdemServicoNaoEncontradaException::comId($input->idOrdemServico);

        $servicos = array_map(
            fn(array $item) => new ServicoOrdemServico(
                idServico: $item['id'],
                quantidade: $item['quantidade'],
                valorUnitario: 0,
            ),
            $input->itens,
        );

        if ($input->substituir) {
            $this->itensGateway->substituirServicos($ordemServico, $servicos);
        } else {
            $this->itensGateway->adicionarServicos($ordemServico, $servicos);
        }
    }
}

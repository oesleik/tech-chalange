<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\EditarItensOrdemServico;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;

final class EditarPecasOrdemServicoUseCase implements EditarPecasOrdemServicoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $ordemServicoGateway,
        private readonly ItensOrdemServicoGatewayInterface $itensGateway,
    ) {}

    public function executar(EditarItensInputDTO $input): void {
        $ordemServico = $this->ordemServicoGateway->buscarPorId($input->idOrdemServico)
            ?? throw OrdemServicoNaoEncontradaException::comId($input->idOrdemServico);

        $pecas = array_map(
            fn(array $item) => new PecaOrdemServico(
                idPeca: $item['id'],
                quantidade: $item['quantidade'],
                valorUnitario: 0,
            ),
            $input->itens,
        );

        if ($input->substituir) {
            $this->itensGateway->substituirPecas($ordemServico, $pecas);
        } else {
            $this->itensGateway->adicionarPecas($ordemServico, $pecas);
        }
    }
}

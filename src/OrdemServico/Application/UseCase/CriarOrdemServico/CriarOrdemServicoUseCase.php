<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\CriarOrdemServico;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use DateTime;

final class CriarOrdemServicoUseCase implements CriarOrdemServicoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $gateway,
        private readonly ItensOrdemServicoGatewayInterface $itensGateway,
    ) {}

    public function executar(CriarOrdemServicoInputDTO $input): OrdemServico {
        $ordemServico = new OrdemServico(
            id: 0,
            idCliente: $input->idCliente,
            idVeiculo: $input->idVeiculo,
            situacao: SituacaoOrdemServicoEnum::RECEBIDA,
            valorTotal: 0,
            dataSolicitacao: new DateTime(),
        );

        $ordemServico = $this->gateway->inserir($ordemServico);

        if (count($input->pecas) !== 0) {
            $this->itensGateway->adicionarPecas(
                $ordemServico,
                array_map(
                    fn(array $item) => new PecaOrdemServico(
                        idPeca: $item['id_peca'],
                        quantidade: $item['quantidade'],
                        valorUnitario: 0,
                    ),
                    $input->pecas,
                ),
            );
        }

        if (count($input->servicos) !== 0) {
            $this->itensGateway->adicionarServicos(
                $ordemServico,
                array_map(
                    fn(array $item) => new ServicoOrdemServico(
                        idServico: $item['id_servico'],
                        quantidade: $item['quantidade'],
                        valorUnitario: 0,
                    ),
                    $input->servicos,
                ),
            );
        }

        return $ordemServico;
    }
}

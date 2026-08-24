<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\CriarOrdemServico;

use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use DateTime;

final class CriarOrdemServicoUseCase implements CriarOrdemServicoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $gateway,
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

        return $this->gateway->inserir($ordemServico);
    }
}

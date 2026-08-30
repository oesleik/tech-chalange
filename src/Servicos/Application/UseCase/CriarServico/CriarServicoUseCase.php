<?php

declare(strict_types=1);

namespace App\Servicos\Application\UseCase\CriarServico;

use App\Servicos\Application\Gateway\ServicoGatewayInterface;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;

class CriarServicoUseCase {
    public function __construct(private readonly ServicoGatewayInterface $gateway) {}

    public function executar(CriarServicoInputDTO $input): Servico {
        $servico = Servico::criar(
            $input->descricao,
            new ValorUnitario($input->valorUnitario),
        );

        return $this->gateway->inserir($servico);
    }
}

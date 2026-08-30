<?php

declare(strict_types=1);

namespace App\Servicos\Application\UseCase\EditarServico;

use App\Servicos\Application\Gateway\ServicoGatewayInterface;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\Exception\ServicoNaoEncontradoException;
use App\Servicos\Domain\ValueObject\ValorUnitario;

class EditarServicoUseCase {
    public function __construct(private readonly ServicoGatewayInterface $gateway) {}

    public function executar(int $id, EditarServicoInputDTO $input): Servico {
        $servico = $this->gateway->buscarPorId($id)
            ?? throw ServicoNaoEncontradoException::comId($id);

        if ($input->descricao !== null) {
            $servico = $servico->comDescricao($input->descricao);
        }
        if ($input->valorUnitario !== null) {
            $servico = $servico->comValorUnitario(new ValorUnitario($input->valorUnitario));
        }

        return $this->gateway->atualizar($servico);
    }
}

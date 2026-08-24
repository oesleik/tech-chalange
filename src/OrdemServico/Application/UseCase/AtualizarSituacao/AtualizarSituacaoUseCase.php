<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\AtualizarSituacao;

use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Domain\Exception\SituacaoBloqueadaException;
use DateTime;
use InvalidArgumentException;

final class AtualizarSituacaoUseCase implements AtualizarSituacaoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $gateway,
    ) {}

    public function executar(AtualizarSituacaoInputDTO $input): OrdemServico {
        $ordemServico = $this->gateway->buscarPorId($input->idOrdemServico)
            ?? throw OrdemServicoNaoEncontradaException::comId($input->idOrdemServico);

        if ($ordemServico->id() === 0) {
            throw new InvalidArgumentException('Id da ordem de serviço não informado');
        }

        if ($ordemServico->situacao() === $input->novaSituacao) {
            return $ordemServico;
        }

        if (!$ordemServico->situacao()->podeAlterarSituacao($input->novaSituacao)) {
            throw new SituacaoBloqueadaException(sprintf(
                'Não é possível alterar uma ordem de serviço de %s para %s.',
                $ordemServico->situacao()->getFormattedValue(),
                $input->novaSituacao->getFormattedValue(),
            ));
        }

        $ordemServico = $ordemServico->comSituacao($input->novaSituacao);

        if ($input->novaSituacao->deveModificarDataAprovacao()) {
            $ordemServico = $ordemServico->comDataAprovacao(new DateTime());
        }

        return $this->gateway->atualizarSituacao($ordemServico);
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\AtualizarSituacao;

use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\Metrics\OrdemServicoMetricsInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Domain\Exception\SituacaoBloqueadaException;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;

final class AtualizarSituacaoUseCase implements AtualizarSituacaoUseCaseInterface {
    public function __construct(
        private readonly OrdemServicoGatewayInterface $gateway,
        private readonly OrdemServicoMetricsInterface $metrics,
        private readonly ClockInterface $clock,
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

        $anterior = $ordemServico;
        $agora = $this->agora();
        $ordemServico = $ordemServico->comSituacao($input->novaSituacao, $agora);

        if ($input->novaSituacao->deveModificarDataAprovacao()) {
            $ordemServico = $ordemServico->comDataAprovacao(clone $agora);
        }

        $ordemServico = $this->gateway->atualizarSituacao($ordemServico);

        $this->metrics->registrarTransicao(
            $anterior->situacao(),
            $input->novaSituacao,
            $this->milissegundosEntre($anterior->dataSituacao(), $agora),
        );

        return $ordemServico;
    }

    // As datas são gravadas sem fuso. Mantém o fuso padrão do PHP, o mesmo usado na leitura.
    private function agora(): DateTime {
        return DateTime::createFromImmutable($this->clock->now())
            ->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    private function milissegundosEntre(DateTime $inicio, DateTime $fim): float {
        $diferenca = (float) $fim->format('U.u') - (float) $inicio->format('U.u');

        return max(0.0, $diferenca * 1000);
    }
}

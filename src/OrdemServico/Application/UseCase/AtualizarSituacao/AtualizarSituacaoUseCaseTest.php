<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\Metrics\OrdemServicoMetricsInterface;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoInputDTO;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoUseCase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Domain\Exception\SituacaoBloqueadaException;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class AtualizarSituacaoUseCaseTest extends TestCase {
    public function testAtualizaSituacaoComSucesso(): void {
        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime());

        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->expects($this->once())->method('buscarPorId')->with(1)->willReturn($os);
        $gateway->expects($this->once())
            ->method('atualizarSituacao')
            ->with($this->callback(fn(OrdemServico $o) => $o->situacao() === SituacaoOrdemServicoEnum::EM_DIAGNOSTICO))
            ->willReturnArgument(0);

        $resultado = $this->useCase($gateway)->executar(
            new AtualizarSituacaoInputDTO(1, SituacaoOrdemServicoEnum::EM_DIAGNOSTICO),
        );

        $this->assertSame(SituacaoOrdemServicoEnum::EM_DIAGNOSTICO, $resultado->situacao());
    }

    public function testDefineDataAprovacaoAoAprovar(): void {
        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO, 0, new DateTime());

        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->willReturn($os);
        $gateway->expects($this->once())
            ->method('atualizarSituacao')
            ->with($this->callback(fn(OrdemServico $o) => $o->dataAprovacao() !== null))
            ->willReturnArgument(0);

        $this->useCase($gateway)->executar(
            new AtualizarSituacaoInputDTO(1, SituacaoOrdemServicoEnum::APROVADA),
        );
    }

    public function testRetornaMesmaOrdemQuandoSituacaoJaEIgual(): void {
        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime());

        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->willReturn($os);
        $gateway->expects($this->never())->method('atualizarSituacao');

        $resultado = $this->useCase($gateway)->executar(
            new AtualizarSituacaoInputDTO(1, SituacaoOrdemServicoEnum::RECEBIDA),
        );

        $this->assertSame($os, $resultado);
    }

    public function testLancaExcecaoQuandoTransicaoNaoPermitida(): void {
        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime());

        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->willReturn($os);
        $gateway->expects($this->never())->method('atualizarSituacao');

        $this->expectException(SituacaoBloqueadaException::class);
        $this->useCase($gateway)->executar(
            new AtualizarSituacaoInputDTO(1, SituacaoOrdemServicoEnum::FINALIZADA),
        );
    }

    public function testLancaExcecaoQuandoOrdemNaoEncontrada(): void {
        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->willReturn(null);

        $this->expectException(OrdemServicoNaoEncontradaException::class);
        $this->useCase($gateway)->executar(
            new AtualizarSituacaoInputDTO(99, SituacaoOrdemServicoEnum::EM_DIAGNOSTICO),
        );
    }

    public function testLancaExcecaoQuandoIdDaOrdemEZero(): void {
        $os = new OrdemServico(0, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime());

        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->willReturn($os);

        $this->expectException(InvalidArgumentException::class);
        $this->useCase($gateway)->executar(
            new AtualizarSituacaoInputDTO(0, SituacaoOrdemServicoEnum::EM_DIAGNOSTICO),
        );
    }

    public function testRegistraTransicaoComTempoNaSituacaoAnterior(): void {
        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::EM_DIAGNOSTICO, 0, new DateTime('2024-01-01 08:00:00'), dataSituacao: new DateTime('2024-01-01 10:00:00'));
        // O relógio devolve UTC; a data gravada está no fuso padrão do PHP.
        $agora = new DateTimeImmutable('2024-01-01 10:00:01.500')->setTimezone(new DateTimeZone('UTC'));

        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->willReturn($os);
        $gateway->method('atualizarSituacao')->willReturnArgument(0);

        $metrics = $this->createMock(OrdemServicoMetricsInterface::class);
        $metrics->expects($this->once())
            ->method('registrarTransicao')
            ->with(
                SituacaoOrdemServicoEnum::EM_DIAGNOSTICO,
                SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO,
                $this->equalToWithDelta(1500.0, 0.01),
            );

        $this->useCase($gateway, $metrics, $agora)->executar(
            new AtualizarSituacaoInputDTO(1, SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO),
        );
    }

    private function useCase(
        OrdemServicoGatewayInterface $gateway,
        ?OrdemServicoMetricsInterface $metrics = null,
        ?DateTimeImmutable $agora = null,
    ): AtualizarSituacaoUseCase {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn($agora ?? new DateTimeImmutable());

        return new AtualizarSituacaoUseCase(
            $gateway,
            $metrics ?? $this->createStub(OrdemServicoMetricsInterface::class),
            $clock,
        );
    }
}

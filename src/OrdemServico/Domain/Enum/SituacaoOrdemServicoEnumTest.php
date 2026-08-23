<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Domain\Enum;

use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use PHPUnit\Framework\TestCase;

final class SituacaoOrdemServicoEnumTest extends TestCase {
    public function testPodeAlterarDeRecebidaParaEmDiagnostico(): void {
        $this->assertTrue(SituacaoOrdemServicoEnum::RECEBIDA->podeAlterarSituacao(SituacaoOrdemServicoEnum::EM_DIAGNOSTICO));
    }

    public function testNaoPodeAlterarDeRecebidaParaFinalizada(): void {
        $this->assertFalse(SituacaoOrdemServicoEnum::RECEBIDA->podeAlterarSituacao(SituacaoOrdemServicoEnum::FINALIZADA));
    }

    public function testPodeAlterarDeAguardandoAprovacaoParaAprovadaOuRejeitada(): void {
        $this->assertTrue(SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO->podeAlterarSituacao(SituacaoOrdemServicoEnum::APROVADA));
        $this->assertTrue(SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO->podeAlterarSituacao(SituacaoOrdemServicoEnum::REJEITADA));
    }

    public function testPodeAlterarDeFinalizadaParaEntregue(): void {
        $this->assertTrue(SituacaoOrdemServicoEnum::FINALIZADA->podeAlterarSituacao(SituacaoOrdemServicoEnum::ENTREGUE));
    }

    public function testNaoPodeAlterarDeEntregueParaQualquerOutra(): void {
        foreach (SituacaoOrdemServicoEnum::cases() as $situacao) {
            $this->assertFalse($situacao->podeAlterarSituacao(SituacaoOrdemServicoEnum::ENTREGUE) && $situacao === SituacaoOrdemServicoEnum::ENTREGUE);
        }
    }

    public function testDeveModificarDataAprovacaoApenasParaAprovadaERejeitada(): void {
        $this->assertTrue(SituacaoOrdemServicoEnum::APROVADA->deveModificarDataAprovacao());
        $this->assertTrue(SituacaoOrdemServicoEnum::REJEITADA->deveModificarDataAprovacao());
        $this->assertFalse(SituacaoOrdemServicoEnum::EM_EXECUCAO->deveModificarDataAprovacao());
    }

    public function testEstaFinalizadaApenasParaFinalizadaEEntregue(): void {
        $this->assertTrue(SituacaoOrdemServicoEnum::FINALIZADA->estaFinalizada());
        $this->assertTrue(SituacaoOrdemServicoEnum::ENTREGUE->estaFinalizada());
        $this->assertFalse(SituacaoOrdemServicoEnum::RECEBIDA->estaFinalizada());
    }

    public function testGetFormattedValue(): void {
        $this->assertSame('recebida', SituacaoOrdemServicoEnum::RECEBIDA->getFormattedValue());
        $this->assertSame('em diagnóstico', SituacaoOrdemServicoEnum::EM_DIAGNOSTICO->getFormattedValue());
    }

    public function testValuesRetornaTodosOsValoresDoEnum(): void {
        $valores = SituacaoOrdemServicoEnum::values();

        $this->assertContains('Recebida', $valores);
        $this->assertContains('Entregue', $valores);
        $this->assertCount(8, $valores);
    }
}

<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Domain\Entity;

use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use DateTime;
use PHPUnit\Framework\TestCase;

final class OrdemServicoTest extends TestCase {
    public function testGettersRetornamValoresInformados(): void {
        $dataSolicitacao = new DateTime('2026-01-01 10:00:00');
        $os = new OrdemServico(
            id: 1,
            idCliente: 10,
            idVeiculo: 20,
            situacao: SituacaoOrdemServicoEnum::RECEBIDA,
            valorTotal: 100.0,
            dataSolicitacao: $dataSolicitacao,
        );

        $this->assertSame(1, $os->id());
        $this->assertSame(10, $os->idCliente());
        $this->assertSame(20, $os->idVeiculo());
        $this->assertSame(SituacaoOrdemServicoEnum::RECEBIDA, $os->situacao());
        $this->assertSame(100.0, $os->valorTotal());
        $this->assertSame($dataSolicitacao, $os->dataSolicitacao());
        $this->assertNull($os->dataAprovacao());
    }

    public function testComIdRetornaNovaInstanciaSemAlterarOriginal(): void {
        $os = $this->criarOrdemServico();
        $comId = $os->comId(99);

        $this->assertSame(0, $os->id());
        $this->assertSame(99, $comId->id());
    }

    public function testComSituacaoRetornaNovaInstancia(): void {
        $os = $this->criarOrdemServico();
        $atualizada = $os->comSituacao(SituacaoOrdemServicoEnum::EM_DIAGNOSTICO);

        $this->assertSame(SituacaoOrdemServicoEnum::RECEBIDA, $os->situacao());
        $this->assertSame(SituacaoOrdemServicoEnum::EM_DIAGNOSTICO, $atualizada->situacao());
    }

    public function testComValorTotalRetornaNovaInstancia(): void {
        $os = $this->criarOrdemServico();
        $atualizada = $os->comValorTotal(250.5);

        $this->assertSame(0.0, $os->valorTotal());
        $this->assertSame(250.5, $atualizada->valorTotal());
    }

    public function testComDataAprovacaoRetornaNovaInstancia(): void {
        $os = $this->criarOrdemServico();
        $data = new DateTime();
        $atualizada = $os->comDataAprovacao($data);

        $this->assertNull($os->dataAprovacao());
        $this->assertSame($data, $atualizada->dataAprovacao());
    }

    private function criarOrdemServico(): OrdemServico {
        return new OrdemServico(
            id: 0,
            idCliente: 1,
            idVeiculo: 2,
            situacao: SituacaoOrdemServicoEnum::RECEBIDA,
            valorTotal: 0,
            dataSolicitacao: new DateTime(),
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Domain\ValueObject;

use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\ValueObject\FiltroOrdemServico;
use PHPUnit\Framework\TestCase;

final class FiltroOrdemServicoTest extends TestCase {
    public function testValoresPadraoSaoNulosOuZero(): void {
        $filtro = new FiltroOrdemServico();

        $this->assertNull($filtro->situacao);
        $this->assertNull($filtro->idCliente);
        $this->assertNull($filtro->idVeiculo);
        $this->assertSame(0, $filtro->limit);
    }

    public function testArmazenaValoresInformados(): void {
        $filtro = new FiltroOrdemServico(
            situacao: SituacaoOrdemServicoEnum::EM_EXECUCAO,
            idCliente: 1,
            idVeiculo: 2,
            limit: 10,
        );

        $this->assertSame(SituacaoOrdemServicoEnum::EM_EXECUCAO, $filtro->situacao);
        $this->assertSame(1, $filtro->idCliente);
        $this->assertSame(2, $filtro->idVeiculo);
        $this->assertSame(10, $filtro->limit);
    }
}

<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Infrastructure\Persistence;

use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Infrastructure\Persistence\OrdemServicoMapper;
use DateTime;
use PHPUnit\Framework\TestCase;

final class OrdemServicoMapperTest extends TestCase {
    public function testMapeiaLinhaCompletaComDataAprovacao(): void {
        $os = OrdemServicoMapper::paraEntidade([
            'id' => 1,
            'id_cliente' => 10,
            'id_veiculo' => 20,
            'situacao' => SituacaoOrdemServicoEnum::FINALIZADA->value,
            'valor_total' => 150.5,
            'data_solicitacao' => '2024-01-01 10:00:00',
            'data_aprovacao' => '2024-01-02 10:00:00',
        ]);

        $this->assertInstanceOf(OrdemServico::class, $os);
        $this->assertSame(1, $os->id());
        $this->assertSame(10, $os->idCliente());
        $this->assertSame(20, $os->idVeiculo());
        $this->assertSame(SituacaoOrdemServicoEnum::FINALIZADA, $os->situacao());
        $this->assertSame(150.5, $os->valorTotal());
        $this->assertInstanceOf(DateTime::class, $os->dataAprovacao());
    }

    public function testMapeiaLinhaSemDataAprovacaoNemValorTotal(): void {
        $os = OrdemServicoMapper::paraEntidade([
            'id' => 1,
            'id_cliente' => 10,
            'id_veiculo' => 20,
            'situacao' => SituacaoOrdemServicoEnum::RECEBIDA->value,
            'data_solicitacao' => '2024-01-01 10:00:00',
        ]);

        $this->assertSame(0.0, $os->valorTotal());
        $this->assertNull($os->dataAprovacao());
    }

    public function testMapeiaLinhaComDataAprovacaoNula(): void {
        $os = OrdemServicoMapper::paraEntidade([
            'id' => 1,
            'id_cliente' => 10,
            'id_veiculo' => 20,
            'situacao' => SituacaoOrdemServicoEnum::RECEBIDA->value,
            'valor_total' => 0,
            'data_solicitacao' => '2024-01-01 10:00:00',
            'data_aprovacao' => null,
        ]);

        $this->assertNull($os->dataAprovacao());
    }
}

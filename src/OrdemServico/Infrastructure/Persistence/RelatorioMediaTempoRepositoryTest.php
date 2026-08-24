<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Infrastructure\Persistence;

use App\Core\AppDatabase;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Infrastructure\Persistence\RelatorioMediaTempoRepository;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class RelatorioMediaTempoRepositoryTest extends TestCase {
    public function testBuscarMapeiaLinhasParaDTOs(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([SituacaoOrdemServicoEnum::FINALIZADA->value, SituacaoOrdemServicoEnum::ENTREGUE->value]);
        $stmt->method('fetchObject')->willReturnOnConsecutiveCalls(
            (object) [
                'id_servico' => 1,
                'descricao' => 'Troca de óleo',
                'valor_unitario' => 49.90,
                'quantidade_execucoes' => 2,
                'total_tempo_executando' => 4.0,
                'min_tempo_execucao' => 1.5,
                'max_tempo_execucao' => 2.5,
            ],
            false,
        );

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $resultado = new RelatorioMediaTempoRepository($pdo)->buscar();

        $this->assertCount(1, $resultado);
        $this->assertSame(1, $resultado[0]->idServico);
        $this->assertSame(2.0, $resultado[0]->mediaTempo);
    }
}

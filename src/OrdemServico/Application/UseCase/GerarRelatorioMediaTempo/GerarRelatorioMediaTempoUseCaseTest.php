<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\GerarRelatorioMediaTempoUseCase;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\RelatorioMediaTempoRepositoryInterface;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\ServicoRelatorioDTO;
use PHPUnit\Framework\TestCase;

final class GerarRelatorioMediaTempoUseCaseTest extends TestCase {
    public function testExecutarDelegaParaORepositorio(): void {
        $servicos = [
            new ServicoRelatorioDTO(
                idServico: 1,
                descricao: 'Troca de óleo',
                valorUnitario: 49.90,
                mediaTempo: 1.5,
                quantidadeExecucoes: 3,
                totalTempoExecutando: 4.5,
                minTempoExecucao: 1.1,
                maxTempoExecucao: 2.2,
            ),
        ];

        $repository = $this->createMock(RelatorioMediaTempoRepositoryInterface::class);
        $repository->expects($this->once())->method('buscar')->willReturn($servicos);

        $resultado = new GerarRelatorioMediaTempoUseCase($repository)->executar();

        $this->assertSame($servicos, $resultado);
    }
}

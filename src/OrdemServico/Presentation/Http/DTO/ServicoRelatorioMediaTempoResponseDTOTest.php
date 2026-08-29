<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\ServicoRelatorioDTO;
use App\OrdemServico\Presentation\Http\DTO\ServicoRelatorioMediaTempoResponseDTO;
use PHPUnit\Framework\TestCase;

final class ServicoRelatorioMediaTempoResponseDTOTest extends TestCase {
    public function testFromDTOMapeiaTodosOsCampos(): void {
        $dto = new ServicoRelatorioDTO(
            idServico: 1,
            descricao: 'Troca de óleo',
            valorUnitario: 49.90,
            mediaTempo: 1.5,
            quantidadeExecucoes: 3,
            totalTempoExecutando: 4.5,
            minTempoExecucao: 1.1,
            maxTempoExecucao: 2.2,
        );

        $response = ServicoRelatorioMediaTempoResponseDTO::fromDTO($dto);

        $this->assertSame(1, $response->id_servico);
        $this->assertSame('Troca de óleo', $response->descricao);
        $this->assertSame(49.90, $response->valor_unitario);
        $this->assertSame(1.5, $response->media_tempo);
        $this->assertSame(3, $response->quantidade_execucoes);
        $this->assertSame(4.5, $response->total_tempo_executando);
        $this->assertSame(1.1, $response->min_tempo_execucao);
        $this->assertSame(2.2, $response->max_tempo_execucao);
    }
}

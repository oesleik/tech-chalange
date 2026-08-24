<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Presentation\Http\DTO\ListarOrdensServicoMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ListarOrdensServicoMapperTest extends TestCase {
    public function testSemFiltrosRetornaInputVazio(): void {
        $input = ListarOrdensServicoMapper::fromQueryParams([]);

        $this->assertNull($input->situacao);
        $this->assertNull($input->idCliente);
        $this->assertNull($input->idVeiculo);
    }

    public function testConverteFiltrosInformados(): void {
        $input = ListarOrdensServicoMapper::fromQueryParams([
            'situacao' => 'Aprovada',
            'id_cliente' => '10',
            'id_veiculo' => '20',
        ]);

        $this->assertSame(SituacaoOrdemServicoEnum::APROVADA, $input->situacao);
        $this->assertSame(10, $input->idCliente);
        $this->assertSame(20, $input->idVeiculo);
    }

    public function testLancaExcecaoQuandoSituacaoInvalida(): void {
        $this->expectException(InvalidArgumentException::class);
        ListarOrdensServicoMapper::fromQueryParams(['situacao' => 'Invalida']);
    }

    public function testLancaExcecaoQuandoIdClienteNaoEPositivo(): void {
        $this->expectException(InvalidArgumentException::class);
        ListarOrdensServicoMapper::fromQueryParams(['id_cliente' => '-1']);
    }
}

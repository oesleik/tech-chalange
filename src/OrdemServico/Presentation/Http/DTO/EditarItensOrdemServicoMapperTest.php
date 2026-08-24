<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Presentation\Http\DTO\EditarItensOrdemServicoMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EditarItensOrdemServicoMapperTest extends TestCase {
    public function testParseListaDePecas(): void {
        $input = EditarItensOrdemServicoMapper::parse(
            ['pecas' => [['id_peca' => 5, 'quantidade' => 2]]],
            'id_peca',
            1,
            false,
        );

        $this->assertSame(1, $input->idOrdemServico);
        $this->assertFalse($input->substituir);
        $this->assertSame(['id' => 5, 'quantidade' => 2], $input->itens[0]);
    }

    public function testParseListaDeServicos(): void {
        $input = EditarItensOrdemServicoMapper::parse(
            ['servicos' => [['id_servico' => 8, 'quantidade' => 3]]],
            'id_servico',
            1,
            true,
        );

        $this->assertTrue($input->substituir);
        $this->assertSame(['id' => 8, 'quantidade' => 3], $input->itens[0]);
    }

    public function testLancaExcecaoQuandoListaAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        EditarItensOrdemServicoMapper::parse([], 'id_peca', 1, false);
    }

    public function testLancaExcecaoQuandoItemSemIdPositivo(): void {
        $this->expectException(InvalidArgumentException::class);
        EditarItensOrdemServicoMapper::parse(
            ['pecas' => [['id_peca' => 0, 'quantidade' => 2]]],
            'id_peca',
            1,
            false,
        );
    }

    public function testLancaExcecaoQuandoQuantidadeAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        EditarItensOrdemServicoMapper::parse(
            ['pecas' => [['id_peca' => 5]]],
            'id_peca',
            1,
            false,
        );
    }
}

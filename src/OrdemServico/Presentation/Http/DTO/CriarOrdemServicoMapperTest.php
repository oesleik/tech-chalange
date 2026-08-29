<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Presentation\Http\DTO\CriarOrdemServicoMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CriarOrdemServicoMapperTest extends TestCase {
    public function testParseComDadosValidos(): void {
        $input = CriarOrdemServicoMapper::parse([
            'id_cliente' => 10,
            'id_veiculo' => 20,
            'pecas' => [['id_peca' => 3, 'quantidade' => 2]],
            'servicos' => [['id_servico' => 4, 'quantidade' => 1]],
        ]);

        $this->assertSame(10, $input->idCliente);
        $this->assertSame(20, $input->idVeiculo);
        $this->assertSame([['id_peca' => 3, 'quantidade' => 2]], $input->pecas);
        $this->assertSame([['id_servico' => 4, 'quantidade' => 1]], $input->servicos);
    }

    public function testAceitaPecasEServicosOpcionais(): void {
        $input = CriarOrdemServicoMapper::parse(['id_cliente' => 10, 'id_veiculo' => 20]);

        $this->assertSame([], $input->pecas);
        $this->assertSame([], $input->servicos);
    }

    public function testLancaExcecaoQuandoPecaInvalida(): void {
        $this->expectException(InvalidArgumentException::class);
        CriarOrdemServicoMapper::parse([
            'id_cliente' => 10,
            'id_veiculo' => 20,
            'pecas' => [['id_peca' => -1, 'quantidade' => 1]],
        ]);
    }

    public function testLancaExcecaoQuandoServicoTemQuantidadeInvalida(): void {
        $this->expectException(InvalidArgumentException::class);
        CriarOrdemServicoMapper::parse([
            'id_cliente' => 10,
            'id_veiculo' => 20,
            'servicos' => [['id_servico' => 4, 'quantidade' => 0]],
        ]);
    }

    public function testLancaExcecaoQuandoListaDePecasNaoEArray(): void {
        $this->expectException(InvalidArgumentException::class);
        CriarOrdemServicoMapper::parse([
            'id_cliente' => 10,
            'id_veiculo' => 20,
            'pecas' => 'invalido',
        ]);
    }

    public function testLancaExcecaoQuandoIdClienteAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        CriarOrdemServicoMapper::parse(['id_veiculo' => 20]);
    }

    public function testLancaExcecaoQuandoIdVeiculoNaoEPositivo(): void {
        $this->expectException(InvalidArgumentException::class);
        CriarOrdemServicoMapper::parse(['id_cliente' => 10, 'id_veiculo' => -1]);
    }
}

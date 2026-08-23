<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Presentation\Http\DTO\CriarOrdemServicoMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CriarOrdemServicoMapperTest extends TestCase {
    public function testParseComDadosValidos(): void {
        $input = CriarOrdemServicoMapper::parse(['id_cliente' => 10, 'id_veiculo' => 20]);

        $this->assertSame(10, $input->idCliente);
        $this->assertSame(20, $input->idVeiculo);
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

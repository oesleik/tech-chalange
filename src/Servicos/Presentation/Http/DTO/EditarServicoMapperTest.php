<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\DTO;

use App\Servicos\Presentation\Http\DTO\EditarServicoMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EditarServicoMapperTest extends TestCase {
    public function testParseComPayloadVazioRetornaTudoNulo(): void {
        $input = EditarServicoMapper::parse([]);

        $this->assertNull($input->descricao);
        $this->assertNull($input->valorUnitario);
    }

    public function testParseComApenasDescricao(): void {
        $input = EditarServicoMapper::parse(['descricao' => 'Diagnóstico']);

        $this->assertSame('Diagnóstico', $input->descricao);
        $this->assertNull($input->valorUnitario);
    }

    public function testParseComApenasValorUnitario(): void {
        $input = EditarServicoMapper::parse(['valor_unitario' => 80]);

        $this->assertNull($input->descricao);
        $this->assertSame(80.0, $input->valorUnitario);
    }

    public function testParseFazTrimNaDescricao(): void {
        $input = EditarServicoMapper::parse(['descricao' => '  Revisão  ']);

        $this->assertSame('Revisão', $input->descricao);
    }

    public function testIgnoraCampoDescricaoQuandoNull(): void {
        $input = EditarServicoMapper::parse(['descricao' => null, 'valor_unitario' => 10]);

        $this->assertNull($input->descricao);
        $this->assertSame(10.0, $input->valorUnitario);
    }

    public function testIgnoraCampoValorUnitarioQuandoNull(): void {
        $input = EditarServicoMapper::parse(['descricao' => 'Revisão', 'valor_unitario' => null]);

        $this->assertSame('Revisão', $input->descricao);
        $this->assertNull($input->valorUnitario);
    }

    public function testLancaExcecaoQuandoDescricaoNaoEString(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição deve ser string.');
        EditarServicoMapper::parse(['descricao' => 123]);
    }

    public function testLancaExcecaoQuandoDescricaoViraVaziaAposTrim(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição não pode ser vazia.');
        EditarServicoMapper::parse(['descricao' => '   ']);
    }

    public function testLancaExcecaoQuandoValorUnitarioNaoNumerico(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário deve ser numérico.');
        EditarServicoMapper::parse(['valor_unitario' => 'abc']);
    }

    public function testLancaExcecaoQuandoValorUnitarioNegativo(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário não pode ser negativo.');
        EditarServicoMapper::parse(['valor_unitario' => -5]);
    }
}

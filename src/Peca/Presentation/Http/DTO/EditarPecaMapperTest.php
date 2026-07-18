<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\DTO;

use App\Peca\Presentation\Http\DTO\EditarPecaMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EditarPecaMapperTest extends TestCase {
    public function testParseComPayloadVazioRetornaTudoNulo(): void {
        $input = EditarPecaMapper::parse([]);

        $this->assertNull($input->descricao);
        $this->assertNull($input->valorUnitario);
    }

    public function testParseComApenasDescricao(): void {
        $input = EditarPecaMapper::parse(['descricao' => 'Filtro de óleo premium']);

        $this->assertSame('Filtro de óleo premium', $input->descricao);
        $this->assertNull($input->valorUnitario);
    }

    public function testParseComApenasValorUnitario(): void {
        $input = EditarPecaMapper::parse(['valor_unitario' => 59.90]);

        $this->assertNull($input->descricao);
        $this->assertSame(59.90, $input->valorUnitario);
    }

    public function testParseFazTrimNaDescricao(): void {
        $input = EditarPecaMapper::parse(['descricao' => '  Correia dentada  ']);

        $this->assertSame('Correia dentada', $input->descricao);
    }

    public function testIgnoraCampoDescricaoQuandoNull(): void {
        $input = EditarPecaMapper::parse(['descricao' => null, 'valor_unitario' => 10]);

        $this->assertNull($input->descricao);
        $this->assertSame(10.0, $input->valorUnitario);
    }

    public function testIgnoraCampoValorUnitarioQuandoNull(): void {
        $input = EditarPecaMapper::parse(['descricao' => 'Correia', 'valor_unitario' => null]);

        $this->assertSame('Correia', $input->descricao);
        $this->assertNull($input->valorUnitario);
    }

    public function testLancaExcecaoQuandoDescricaoNaoEString(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição deve ser string.');
        EditarPecaMapper::parse(['descricao' => 123]);
    }

    public function testLancaExcecaoQuandoDescricaoViraVaziaAposTrim(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição não pode ser vazia.');
        EditarPecaMapper::parse(['descricao' => '   ']);
    }

    public function testLancaExcecaoQuandoValorUnitarioNaoNumerico(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário deve ser numérico.');
        EditarPecaMapper::parse(['valor_unitario' => 'abc']);
    }

    public function testLancaExcecaoQuandoValorUnitarioNegativo(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário não pode ser negativo.');
        EditarPecaMapper::parse(['valor_unitario' => -5]);
    }
}

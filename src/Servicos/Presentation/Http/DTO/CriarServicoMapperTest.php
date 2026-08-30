<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\DTO;

use App\Servicos\Presentation\Http\DTO\CriarServicoMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CriarServicoMapperTest extends TestCase {
    public function testParseComPayloadValido(): void {
        $input = CriarServicoMapper::parse([
            'descricao' => 'Troca de óleo',
            'valor_unitario' => 49.90,
        ]);

        $this->assertSame('Troca de óleo', $input->descricao);
        $this->assertSame(49.90, $input->valorUnitario);
    }

    public function testParseFazTrimNaDescricao(): void {
        $input = CriarServicoMapper::parse([
            'descricao' => '  Revisão  ',
            'valor_unitario' => 150,
        ]);

        $this->assertSame('Revisão', $input->descricao);
        $this->assertSame(150.0, $input->valorUnitario);
    }

    public function testParseAceitaValorUnitarioZero(): void {
        $input = CriarServicoMapper::parse([
            'descricao' => 'Cortesia',
            'valor_unitario' => 0,
        ]);

        $this->assertSame(0.0, $input->valorUnitario);
    }

    public function testParseAceitaValorUnitarioComoStringNumerica(): void {
        $input = CriarServicoMapper::parse([
            'descricao' => 'Revisão',
            'valor_unitario' => '80.5',
        ]);

        $this->assertSame(80.5, $input->valorUnitario);
    }

    public function testLancaExcecaoQuandoDescricaoAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição é obrigatória.');
        CriarServicoMapper::parse(['valor_unitario' => 10]);
    }

    public function testLancaExcecaoQuandoDescricaoVazia(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição é obrigatória.');
        CriarServicoMapper::parse(['descricao' => '', 'valor_unitario' => 10]);
    }

    public function testLancaExcecaoQuandoDescricaoNaoEString(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição é obrigatória.');
        CriarServicoMapper::parse(['descricao' => 123, 'valor_unitario' => 10]);
    }

    public function testLancaExcecaoQuandoValorUnitarioAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário é obrigatório e deve ser numérico.');
        CriarServicoMapper::parse(['descricao' => 'Revisão']);
    }

    public function testLancaExcecaoQuandoValorUnitarioNaoNumerico(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário é obrigatório e deve ser numérico.');
        CriarServicoMapper::parse(['descricao' => 'Revisão', 'valor_unitario' => 'abc']);
    }

    public function testLancaExcecaoQuandoValorUnitarioNegativo(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário não pode ser negativo.');
        CriarServicoMapper::parse(['descricao' => 'Revisão', 'valor_unitario' => -1]);
    }
}

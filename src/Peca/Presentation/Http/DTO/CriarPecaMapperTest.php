<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\DTO;

use App\Peca\Presentation\Http\DTO\CriarPecaMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CriarPecaMapperTest extends TestCase {
    public function testParseComDadosValidos(): void {
        $input = CriarPecaMapper::parse([
            'descricao' => 'Filtro de óleo',
            'valor_unitario' => 49.90,
        ]);

        $this->assertSame('Filtro de óleo', $input->descricao);
        $this->assertSame(49.90, $input->valorUnitario);
    }

    public function testParseAceitaValorUnitarioInteiro(): void {
        $input = CriarPecaMapper::parse([
            'descricao' => 'Vela de ignição',
            'valor_unitario' => 30,
        ]);

        $this->assertSame(30.0, $input->valorUnitario);
    }

    public function testParseFazTrimNaDescricao(): void {
        $input = CriarPecaMapper::parse([
            'descricao' => '  Filtro de óleo  ',
            'valor_unitario' => 49.90,
        ]);

        $this->assertSame('Filtro de óleo', $input->descricao);
    }

    public function testLancaExcecaoQuandoDescricaoAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição é obrigatória.');
        CriarPecaMapper::parse(['valor_unitario' => 49.90]);
    }

    public function testLancaExcecaoQuandoDescricaoVazia(): void {
        $this->expectException(InvalidArgumentException::class);
        CriarPecaMapper::parse(['descricao' => '', 'valor_unitario' => 49.90]);
    }

    public function testLancaExcecaoQuandoDescricaoNaoEString(): void {
        $this->expectException(InvalidArgumentException::class);
        CriarPecaMapper::parse(['descricao' => 123, 'valor_unitario' => 49.90]);
    }

    public function testLancaExcecaoQuandoValorUnitarioAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário é obrigatório e deve ser numérico.');
        CriarPecaMapper::parse(['descricao' => 'Filtro de óleo']);
    }

    public function testLancaExcecaoQuandoValorUnitarioNaoNumerico(): void {
        $this->expectException(InvalidArgumentException::class);
        CriarPecaMapper::parse(['descricao' => 'Filtro de óleo', 'valor_unitario' => 'abc']);
    }

    public function testLancaExcecaoQuandoValorUnitarioNegativo(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário não pode ser negativo.');
        CriarPecaMapper::parse(['descricao' => 'Filtro de óleo', 'valor_unitario' => -1]);
    }
}

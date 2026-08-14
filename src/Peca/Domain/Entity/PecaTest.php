<?php

declare(strict_types=1);

namespace Tests\Peca\Domain\Entity;

use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PecaTest extends TestCase {
    public function testCriarGeraPecaSemId(): void {
        $peca = Peca::criar('Filtro de óleo', new ValorUnitario(49.90));

        $this->assertNull($peca->id());
        $this->assertSame('Filtro de óleo', $peca->descricao());
        $this->assertSame(49.90, $peca->valorUnitario()->getValue());
    }

    public function testReconstituirGeraPecaComId(): void {
        $peca = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));

        $this->assertSame(1, $peca->id());
    }

    public function testTrimNaDescricao(): void {
        $peca = Peca::criar('  Filtro de óleo  ', new ValorUnitario(49.90));

        $this->assertSame('Filtro de óleo', $peca->descricao());
    }

    public function testLancaExcecaoQuandoDescricaoVazia(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição é obrigatória.');
        Peca::criar('', new ValorUnitario(49.90));
    }

    public function testLancaExcecaoQuandoDescricaoSoEspacos(): void {
        $this->expectException(InvalidArgumentException::class);
        Peca::criar('   ', new ValorUnitario(49.90));
    }

    public function testComIdRetornaNovaInstanciaComIdDefinido(): void {
        $peca = Peca::criar('Filtro de óleo', new ValorUnitario(49.90));
        $comId = $peca->comId(5);

        $this->assertNull($peca->id());
        $this->assertSame(5, $comId->id());
        $this->assertNotSame($peca, $comId);
    }

    public function testComDescricaoRetornaNovaInstanciaComDescricaoAlterada(): void {
        $peca = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));
        $atualizada = $peca->comDescricao('Filtro de óleo premium');

        $this->assertSame('Filtro de óleo', $peca->descricao());
        $this->assertSame('Filtro de óleo premium', $atualizada->descricao());
        $this->assertSame(1, $atualizada->id());
    }

    public function testComValorUnitarioRetornaNovaInstanciaComValorAlterado(): void {
        $peca = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));
        $novoValor = new ValorUnitario(59.90);
        $atualizada = $peca->comValorUnitario($novoValor);

        $this->assertSame(49.90, $peca->valorUnitario()->getValue());
        $this->assertSame(59.90, $atualizada->valorUnitario()->getValue());
    }
}

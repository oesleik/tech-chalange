<?php

declare(strict_types=1);

namespace Tests\Servicos\Domain\Entity;

use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ServicoTest extends TestCase {
    public function testCriarGeraServicoSemId(): void {
        $servico = Servico::criar('Troca de óleo', new ValorUnitario(49.90));

        $this->assertNull($servico->id());
        $this->assertSame('Troca de óleo', $servico->descricao());
        $this->assertSame(49.90, $servico->valorUnitario()->getValue());
    }

    public function testReconstituirGeraServicoComId(): void {
        $servico = Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(49.90));

        $this->assertSame(1, $servico->id());
    }

    public function testTrimNaDescricao(): void {
        $servico = Servico::criar('  Troca de óleo  ', new ValorUnitario(49.90));

        $this->assertSame('Troca de óleo', $servico->descricao());
    }

    public function testLancaExcecaoQuandoDescricaoVazia(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição é obrigatória.');
        Servico::criar('', new ValorUnitario(49.90));
    }

    public function testLancaExcecaoQuandoDescricaoSoEspacos(): void {
        $this->expectException(InvalidArgumentException::class);
        Servico::criar('   ', new ValorUnitario(49.90));
    }

    public function testComIdRetornaNovaInstanciaComIdDefinido(): void {
        $servico = Servico::criar('Troca de óleo', new ValorUnitario(49.90));
        $comId = $servico->comId(5);

        $this->assertNull($servico->id());
        $this->assertSame(5, $comId->id());
        $this->assertNotSame($servico, $comId);
    }

    public function testComDescricaoRetornaNovaInstanciaComDescricaoAlterada(): void {
        $servico = Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(49.90));
        $atualizado = $servico->comDescricao('Troca de óleo premium');

        $this->assertSame('Troca de óleo', $servico->descricao());
        $this->assertSame('Troca de óleo premium', $atualizado->descricao());
        $this->assertSame(1, $atualizado->id());
    }

    public function testComValorUnitarioRetornaNovaInstanciaComValorAlterado(): void {
        $servico = Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(49.90));
        $novoValor = new ValorUnitario(59.90);
        $atualizado = $servico->comValorUnitario($novoValor);

        $this->assertSame(49.90, $servico->valorUnitario()->getValue());
        $this->assertSame(59.90, $atualizado->valorUnitario()->getValue());
    }
}

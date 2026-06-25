<?php

declare(strict_types=1);

use App\Servicos\Model\ServicoModel;
use PHPUnit\Framework\TestCase;

class ServicoModelTest extends TestCase {
    public function testImmutable(): void {
        $base = new ServicoModel(
            id: 123,
            descricao: "Revisão",
            valorUnitario: 150,
        );

        $this->assertEquals(123, $base->getId());
        $this->assertEquals("Revisão", $base->getDescricao());
        $this->assertEquals(150, $base->getValorUnitario());

        $withId = $base->withId(456);
        $this->assertEquals(456, $withId->getId());

        $withDescricao = $base->withDescricao("Diagnóstico");
        $this->assertEquals("Diagnóstico", $withDescricao->getDescricao());

        $withValorUnitario = $base->withValorUnitario(80);
        $this->assertEquals(80, $withValorUnitario->getValorUnitario());

        $this->assertNotSame($base, $withId);
        $this->assertNotSame($base, $withDescricao);
        $this->assertNotSame($base, $withValorUnitario);
    }
}

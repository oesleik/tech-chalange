<?php

declare(strict_types=1);

use App\Peca\Model\PecaModel;
use PHPUnit\Framework\TestCase;

class PecaModelTest extends TestCase {
    public function testImmutable(): void {
        $base = new PecaModel(
            id: 123,
            descricao: "Vela",
            valorUnitario: 22.75
        );

        $this->assertEquals(123, $base->getId());
        $this->assertEquals("Vela", $base->getDescricao());
        $this->assertEquals(22.75, $base->getValorUnitario());

        $withId = $base->withId(456);
        $this->assertEquals(456, $withId->getId());

        $withDescricao = $base->withDescricao("Correia");
        $this->assertEquals("Correia", $withDescricao->getDescricao());

        $withValorUnitario = $base->withValorUnitario(245);
        $this->assertEquals(245, $withValorUnitario->getValorUnitario());

        $this->assertNotSame($base, $withId);
        $this->assertNotSame($base, $withDescricao);
        $this->assertNotSame($base, $withValorUnitario);
    }
}

<?php

declare(strict_types=1);

use App\Veiculos\Model\VeiculoModel;
use PHPUnit\Framework\TestCase;

class VeiculoModelTest extends TestCase {
    public function testImmutable(): void {
        $base = new VeiculoModel(
            id: 123,
            placa: "ABC-1234",
			marca: "Volkswagen",
			modelo: "Gol",
        );

        $this->assertEquals(123, $base->getId());
        $this->assertEquals("ABC-1234", $base->getPlaca());
        $this->assertEquals("Volkswagen", $base->getMarca());
        $this->assertEquals("Gol", $base->getModelo());

        $withId = $base->withId(456);
        $this->assertEquals(456, $withId->getId());

        $withPlaca = $base->withPlaca("AAA-4D56");
        $this->assertEquals("AAA-4D56", $withPlaca->getPlaca());

        $withMarca = $base->withMarca("Fiat");
        $this->assertEquals("Fiat", $withMarca->getMarca());

        $withModelo = $base->withModelo("Uno");
        $this->assertEquals("Uno", $withModelo->getModelo());

        $this->assertNotSame($base, $withId);
        $this->assertNotSame($base, $withPlaca);
        $this->assertNotSame($base, $withMarca);
        $this->assertNotSame($base, $withModelo);
    }
}

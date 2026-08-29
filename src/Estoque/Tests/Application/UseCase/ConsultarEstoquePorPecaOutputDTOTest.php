<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Application\UseCase;

use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaOutputDTO;
use PHPUnit\Framework\TestCase;

final class ConsultarEstoquePorPecaOutputDTOTest extends TestCase {
    public function testMantemValoresInformados(): void {
        $dto = new ConsultarEstoquePorPecaOutputDTO(12, -2);

        $this->assertSame(12, $dto->pecaId);
        $this->assertSame(-2, $dto->estoqueAtual);
    }
}

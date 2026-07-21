<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\DTO;

use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;
use App\Peca\Presentation\Http\DTO\PecaResponseDTO;
use PHPUnit\Framework\TestCase;

final class PecaResponseDTOTest extends TestCase {
    public function testFromEntityConverteCamposCorretamente(): void {
        $peca = Peca::reconstituir(7, 'Filtro de óleo', new ValorUnitario(49.90));

        $dto = PecaResponseDTO::fromEntity($peca);

        $this->assertSame(7, $dto->id);
        $this->assertSame('Filtro de óleo', $dto->descricao);
        $this->assertSame(49.90, $dto->valor_unitario);
    }

    public function testConstrutorArmazenaValoresDiretamente(): void {
        $dto = new PecaResponseDTO(id: 1, descricao: 'Vela', valor_unitario: 10.5);

        $this->assertSame(1, $dto->id);
        $this->assertSame('Vela', $dto->descricao);
        $this->assertSame(10.5, $dto->valor_unitario);
    }
}

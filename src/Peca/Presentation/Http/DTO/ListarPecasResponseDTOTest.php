<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\DTO;

use App\Peca\Presentation\Http\DTO\ListarPecasResponseDTO;
use App\Peca\Presentation\Http\DTO\PecaResponseDTO;
use PHPUnit\Framework\TestCase;

final class ListarPecasResponseDTOTest extends TestCase {
    public function testArmazenaListaDePecas(): void {
        $pecas = [
            new PecaResponseDTO(1, 'Filtro de óleo', 49.90),
            new PecaResponseDTO(2, 'Correia dentada', 120.0),
        ];

        $dto = new ListarPecasResponseDTO($pecas);

        $this->assertCount(2, $dto->pecas);
        $this->assertSame($pecas, $dto->pecas);
    }

    public function testAceitaListaVazia(): void {
        $dto = new ListarPecasResponseDTO([]);

        $this->assertSame([], $dto->pecas);
    }
}

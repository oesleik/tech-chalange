<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\DTO;

use App\Servicos\Presentation\Http\DTO\ListarServicosResponseDTO;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use PHPUnit\Framework\TestCase;

final class ListarServicosResponseDTOTest extends TestCase {
    public function testArmazenaAListaDeServicos(): void {
        $servicos = [
            new ServicoResponseDTO(1, 'Troca de óleo', 49.90),
            new ServicoResponseDTO(2, 'Revisão', 150.0),
        ];

        $dto = new ListarServicosResponseDTO($servicos);

        $this->assertCount(2, $dto->servicos);
        $this->assertSame($servicos, $dto->servicos);
    }

    public function testAceitaListaVazia(): void {
        $dto = new ListarServicosResponseDTO([]);

        $this->assertSame([], $dto->servicos);
    }
}

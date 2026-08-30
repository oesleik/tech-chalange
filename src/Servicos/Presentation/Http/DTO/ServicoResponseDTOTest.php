<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\DTO;

use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use PHPUnit\Framework\TestCase;

final class ServicoResponseDTOTest extends TestCase {
    public function testFromEntityConverteCamposCorretamente(): void {
        $servico = Servico::reconstituir(7, 'Troca de óleo', new ValorUnitario(49.90));

        $dto = ServicoResponseDTO::fromEntity($servico);

        $this->assertSame(7, $dto->id);
        $this->assertSame('Troca de óleo', $dto->descricao);
        $this->assertSame(49.90, $dto->valor_unitario);
    }

    public function testConstrutorArmazenaValoresDiretamente(): void {
        $dto = new ServicoResponseDTO(id: 1, descricao: 'Revisão', valor_unitario: 150.0);

        $this->assertSame(1, $dto->id);
        $this->assertSame('Revisão', $dto->descricao);
        $this->assertSame(150.0, $dto->valor_unitario);
    }
}

<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use App\OrdemServico\Presentation\Http\DTO\PecaOrdemServicoResponseDTO;
use App\OrdemServico\Presentation\Http\DTO\ServicoOrdemServicoResponseDTO;
use PHPUnit\Framework\TestCase;

final class PecaEServicoResponseDTOTest extends TestCase {
    public function testServicoResponseDTOFromEntityMapeiaCampos(): void {
        $servico = new ServicoOrdemServico(idServico: 5, quantidade: 2, valorUnitario: 100);

        $dto = ServicoOrdemServicoResponseDTO::fromEntity($servico);

        $this->assertSame(5, $dto->id_servico);
        $this->assertSame(2, $dto->quantidade);
    }

    public function testPecaResponseDTOFromEntityMapeiaCampos(): void {
        $peca = new PecaOrdemServico(idPeca: 7, quantidade: 3, valorUnitario: 50);

        $dto = PecaOrdemServicoResponseDTO::fromEntity($peca);

        $this->assertSame(7, $dto->id_peca);
        $this->assertSame(3, $dto->quantidade);
    }
}

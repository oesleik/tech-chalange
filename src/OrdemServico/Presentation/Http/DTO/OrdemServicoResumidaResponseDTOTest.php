<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoResumidaResponseDTO;
use DateTime;
use PHPUnit\Framework\TestCase;

final class OrdemServicoResumidaResponseDTOTest extends TestCase {
    public function testFromEntityConverteCamposCorretamente(): void {
        $os = new OrdemServico(
            id: 1,
            idCliente: 10,
            idVeiculo: 20,
            situacao: SituacaoOrdemServicoEnum::RECEBIDA,
            valorTotal: 150.5,
            dataSolicitacao: new DateTime('2026-01-01 10:00:00'),
        );

        $dto = OrdemServicoResumidaResponseDTO::fromEntity($os);

        $this->assertSame(1, $dto->id);
        $this->assertSame(10, $dto->id_cliente);
        $this->assertSame('Recebida', $dto->situacao);
        $this->assertSame(150.5, $dto->valor_total);
        $this->assertSame('2026-01-01 10:00:00', $dto->data_solicitacao);
        $this->assertNull($dto->data_aprovacao);
    }
}

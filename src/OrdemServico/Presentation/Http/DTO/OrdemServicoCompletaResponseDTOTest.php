<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoOutputDTO;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoCompletaResponseDTO;
use DateTime;
use PHPUnit\Framework\TestCase;

final class OrdemServicoCompletaResponseDTOTest extends TestCase {
    public function testFromOutputDTOConvertePecasEServicos(): void {
        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 50.0, new DateTime('2026-01-01 10:00:00'));
        $output = new ObterOrdemServicoOutputDTO(
            ordemServico: $os,
            pecas: [new PecaOrdemServico(5, 2, 10.0)],
            servicos: [new ServicoOrdemServico(8, 1, 30.0)],
        );

        $dto = OrdemServicoCompletaResponseDTO::fromOutputDTO($output);

        $this->assertSame(1, $dto->id);
        $this->assertCount(1, $dto->pecas);
        $this->assertSame(5, $dto->pecas[0]->id_peca);
        $this->assertCount(1, $dto->servicos);
        $this->assertSame(8, $dto->servicos[0]->id_servico);
    }
}

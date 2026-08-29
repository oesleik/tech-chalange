<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Presentation\Http\DTO;

use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaOutputDTO;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use App\Estoque\Presentation\Http\DTO\EstoqueConsultaResponseDTO;
use App\Estoque\Presentation\Http\DTO\LancamentoResponseDTO;
use PHPUnit\Framework\TestCase;

final class EstoqueResponseDTOTest extends TestCase {
    public function testEstoqueConsultaResponseMapeiaOutputDTO(): void {
        $dto = EstoqueConsultaResponseDTO::fromOutputDTO(new ConsultarEstoquePorPecaOutputDTO(9, 27));

        $this->assertSame(9, $dto->id_peca);
        $this->assertSame(27, $dto->estoque_atual);
    }

    public function testLancamentoResponseMapeiaEntrada(): void {
        $dto = LancamentoResponseDTO::fromEntity(
            LancamentoEstoque::reconstituir(8, 9, 4, TipoLancamentoEnum::ENTRADA)
        );

        $this->assertSame(8, $dto->id);
        $this->assertSame(9, $dto->id_peca);
        $this->assertSame(4, $dto->quantidade);
        $this->assertSame('entrada', $dto->tipo_lancamento);
    }

    public function testLancamentoResponseMapeiaBaixa(): void {
        $dto = LancamentoResponseDTO::fromEntity(
            LancamentoEstoque::reconstituir(11, 3, 2, TipoLancamentoEnum::BAIXA)
        );

        $this->assertSame(11, $dto->id);
        $this->assertSame('baixa', $dto->tipo_lancamento);
    }
}

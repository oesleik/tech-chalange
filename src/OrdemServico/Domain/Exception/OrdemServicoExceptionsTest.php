<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Domain\Exception;

use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Domain\Exception\SituacaoBloqueadaException;
use PHPUnit\Framework\TestCase;

final class OrdemServicoExceptionsTest extends TestCase {
    public function testNaoEncontradaComIdMontaMensagem(): void {
        $excecao = OrdemServicoNaoEncontradaException::comId(5);

        $this->assertSame('Ordem de serviço com id 5 não encontrada.', $excecao->getMessage());
    }

    public function testSituacaoBloqueadaAceitaMensagemCustomizada(): void {
        $excecao = new SituacaoBloqueadaException('Transição não permitida.');

        $this->assertSame('Transição não permitida.', $excecao->getMessage());
    }
}

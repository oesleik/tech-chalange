<?php

declare(strict_types=1);

namespace Tests\Servicos\Application\UseCase;

use App\Servicos\Application\Gateway\ServicoGatewayInterface;
use App\Servicos\Application\UseCase\ObterServico\ObterServicoUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\Exception\ServicoNaoEncontradoException;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use PHPUnit\Framework\TestCase;

final class ObterServicoUseCaseTest extends TestCase {
    public function testRetornaServicoQuandoEncontrado(): void {
        $servico = Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($servico);

        $resultado = new ObterServicoUseCase($gateway)->executar(1);

        $this->assertSame(1, $resultado->id());
        $this->assertSame('Troca de óleo', $resultado->descricao());
    }

    public function testLancaExcecaoQuandoNaoEncontrado(): void {
        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->with(99)->willReturn(null);

        $this->expectException(ServicoNaoEncontradoException::class);
        new ObterServicoUseCase($gateway)->executar(99);
    }
}

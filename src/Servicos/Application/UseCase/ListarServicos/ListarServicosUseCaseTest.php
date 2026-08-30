<?php

declare(strict_types=1);

namespace Tests\Servicos\Application\UseCase;

use App\Servicos\Application\Gateway\ServicoGatewayInterface;
use App\Servicos\Application\UseCase\ListarServicos\ListarServicosUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use PHPUnit\Framework\TestCase;

final class ListarServicosUseCaseTest extends TestCase {
    public function testRetornaListaDeServicos(): void {
        $servicos = [
            Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(49.90)),
            Servico::reconstituir(2, 'Alinhamento', new ValorUnitario(120.0)),
        ];

        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('listar')->willReturn($servicos);

        $resultado = new ListarServicosUseCase($gateway)->executar();

        $this->assertCount(2, $resultado);
        $this->assertSame('Troca de óleo', $resultado[0]->descricao());
        $this->assertSame('Alinhamento', $resultado[1]->descricao());
    }

    public function testRetornaListaVaziaQuandoNaoHaServicos(): void {
        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('listar')->willReturn([]);

        $resultado = new ListarServicosUseCase($gateway)->executar();

        $this->assertSame([], $resultado);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Servicos\Application\UseCase;

use App\Servicos\Application\Gateway\ServicoGatewayInterface;
use App\Servicos\Application\UseCase\CriarServico\CriarServicoInputDTO;
use App\Servicos\Application\UseCase\CriarServico\CriarServicoUseCase;
use App\Servicos\Domain\Entity\Servico;
use PHPUnit\Framework\TestCase;

final class CriarServicoUseCaseTest extends TestCase {
    public function testCriaServicoComSucesso(): void {
        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('inserir')->willReturnCallback(
            fn(Servico $s) => $s->comId(1),
        );

        $resultado = new CriarServicoUseCase($gateway)->executar(
            new CriarServicoInputDTO('Troca de óleo', 49.90),
        );

        $this->assertSame(1, $resultado->id());
        $this->assertSame('Troca de óleo', $resultado->descricao());
        $this->assertSame(49.90, $resultado->valorUnitario()->getValue());
    }

    public function testLancaExcecaoQuandoValorNegativo(): void {
        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->expects($this->never())->method('inserir');

        $this->expectException(\InvalidArgumentException::class);
        new CriarServicoUseCase($gateway)->executar(
            new CriarServicoInputDTO('Troca de óleo', -10.0),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Application\UseCase;

use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueInputDTO;
use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueUseCase;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use PHPUnit\Framework\TestCase;

final class RegistrarBaixaEstoqueLimitesTest extends TestCase {
    public function testPermiteBaixaExatamenteIgualAoEstoqueDisponivel(): void {
        $gateway = $this->createMock(EstoqueGatewayInterface::class);
        $gateway->method('pecaExiste')->with(1)->willReturn(true);
        $gateway->method('calcularEstoqueAtual')->with(1)->willReturn(5);
        $gateway->expects($this->once())
            ->method('inserirLancamento')
            ->with(1, 5, TipoLancamentoEnum::BAIXA)
            ->willReturn(LancamentoEstoque::reconstituir(20, 1, 5, TipoLancamentoEnum::BAIXA));

        $resultado = new RegistrarBaixaEstoqueUseCase($gateway)
            ->executar(new RegistrarBaixaEstoqueInputDTO(1, 5));

        $this->assertSame(5, $resultado->quantidade());
    }
}

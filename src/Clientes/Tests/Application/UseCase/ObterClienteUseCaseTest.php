<?php

declare(strict_types=1);

namespace Tests\Clientes\Application\UseCase;

use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Application\UseCase\ObterCliente\ObterClienteUseCase;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use PHPUnit\Framework\TestCase;

final class ObterClienteUseCaseTest extends TestCase {
    public function testObtemClienteComSucesso(): void {
        $cliente = Cliente::reconstituir(
            1,
            'Cliente',
            new Cpf('52998224725'),
            new Email('cliente@example.com'),
            new Telefone('5412345678'),
        );
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->expects($this->once())->method('buscarPorId')->with(1)->willReturn($cliente);

        $resultado = new ObterClienteUseCase($gateway)->executar(1);

        $this->assertSame($cliente, $resultado);
    }

    public function testLancaExcecaoQuandoNaoEncontraCliente(): void {
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->method('buscarPorId')->willReturn(null);

        $this->expectException(ClienteNaoEncontradoException::class);
        new ObterClienteUseCase($gateway)->executar(1);
    }
}

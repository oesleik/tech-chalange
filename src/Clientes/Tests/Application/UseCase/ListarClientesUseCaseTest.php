<?php

declare(strict_types=1);

namespace Tests\Clientes\Application\UseCase;

use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Application\UseCase\ListarClientes\ListarClientesInputDTO;
use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCase;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use PHPUnit\Framework\TestCase;

final class ListarClientesUseCaseTest extends TestCase {
    public function testListaTodosOsClientes(): void {
        $clientes = [$this->cliente()];
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->expects($this->once())->method('listar')->with(null)->willReturn($clientes);

        $resultado = new ListarClientesUseCase($gateway)->executar(new ListarClientesInputDTO());

        $this->assertSame($clientes, $resultado);
    }

    public function testListaPorCpfCnpj(): void {
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->expects($this->once())
            ->method('listar')
            ->with($this->callback(fn(Cpf $cpf) => $cpf->getValue() === '52998224725'))
            ->willReturn([]);

        $resultado = new ListarClientesUseCase($gateway)->executar(new ListarClientesInputDTO('529.982.247-25'));

        $this->assertSame([], $resultado);
    }

    private function cliente(): Cliente {
        return Cliente::reconstituir(
            1,
            'Cliente',
            new Cpf('52998224725'),
            new Email('cliente@example.com'),
            new Telefone('5412345678'),
        );
    }
}

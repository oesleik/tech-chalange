<?php

declare(strict_types=1);

namespace Tests\Clientes\Application\UseCase;

use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Application\UseCase\EditarCliente\EditarClienteInputDTO;
use App\Clientes\Application\UseCase\EditarCliente\EditarClienteUseCase;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteJaCadastradoException;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use PHPUnit\Framework\TestCase;

final class EditarClienteUseCaseTest extends TestCase {
    public function testEditaClienteComSucesso(): void {
        $cliente = $this->cliente();
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->expects($this->once())->method('buscarPorId')->with(1)->willReturn($cliente);
        $gateway->expects($this->never())->method('buscarPorCpfCnpj');
        $gateway->expects($this->once())->method('atualizar')->willReturnCallback(fn(Cliente $valor) => $valor);

        $resultado = new EditarClienteUseCase($gateway)->executar(1, new EditarClienteInputDTO(
            nome: 'Novo nome',
            email: 'novo@example.com',
            telefone: '5511223344',
        ));

        $this->assertSame('Novo nome', $resultado->nome());
        $this->assertSame('novo@example.com', $resultado->email()->getValue());
        $this->assertSame('5511223344', $resultado->telefone()->getValue());
        $this->assertSame('52998224725', $resultado->cpfCnpj()->getValue());
    }

    public function testLancaExcecaoQuandoClienteNaoExiste(): void {
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->expects($this->once())->method('buscarPorId')->with(99)->willReturn(null);
        $gateway->expects($this->never())->method('atualizar');

        $this->expectException(ClienteNaoEncontradoException::class);
        new EditarClienteUseCase($gateway)->executar(99, new EditarClienteInputDTO(nome: 'Novo nome'));
    }

    public function testNaoPermiteCpfCnpjDuplicadoAoEditar(): void {
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->method('buscarPorId')->willReturn($this->cliente());
        $gateway->expects($this->once())->method('buscarPorCpfCnpj')->willReturn($this->cliente(2));
        $gateway->expects($this->never())->method('atualizar');

        $this->expectException(ClienteJaCadastradoException::class);
        new EditarClienteUseCase($gateway)->executar(1, new EditarClienteInputDTO(cpfCnpj: '12345678909'));
    }

    private function cliente(int $id = 1): Cliente {
        return Cliente::reconstituir(
            $id,
            'Cliente',
            new Cpf('52998224725'),
            new Email('cliente@example.com'),
            new Telefone('5412345678'),
        );
    }
}

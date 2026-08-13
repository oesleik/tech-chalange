<?php

declare(strict_types=1);

namespace Tests\Clientes\Application\UseCase;

use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Application\UseCase\CriarCliente\CriarClienteInputDTO;
use App\Clientes\Application\UseCase\CriarCliente\CriarClienteUseCase;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteJaCadastradoException;
use PHPUnit\Framework\TestCase;

final class CriarClienteUseCaseTest extends TestCase {
    public function testCriaClienteComSucesso(): void {
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->expects($this->once())
            ->method('buscarPorCpfCnpj')
            ->willReturn(null);
        $gateway->expects($this->once())
            ->method('inserir')
            ->willReturnCallback(fn(Cliente $cliente) => $cliente->comId(1));

        $resultado = new CriarClienteUseCase($gateway)->executar(new CriarClienteInputDTO(
            nome: 'Fulano de Tal',
            cpfCnpj: '529.982.247-25',
            email: 'fulano@example.com',
            telefone: '5412345678',
        ));

        $this->assertSame(1, $resultado->id());
        $this->assertSame('Fulano de Tal', $resultado->nome());
        $this->assertSame('52998224725', $resultado->cpfCnpj()->getValue());
    }

    public function testNaoCriaClienteComCpfCnpjDuplicado(): void {
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->expects($this->once())->method('buscarPorCpfCnpj')->willReturn(
            Cliente::reconstituir(
                1,
                'Cliente existente',
                new \App\Clientes\Domain\ValueObject\Cpf('52998224725'),
                new \App\Clientes\Domain\ValueObject\Email('existente@example.com'),
                new \App\Clientes\Domain\ValueObject\Telefone('5412345678'),
            ),
        );
        $gateway->expects($this->never())->method('inserir');

        $this->expectException(ClienteJaCadastradoException::class);
        new CriarClienteUseCase($gateway)->executar(new CriarClienteInputDTO(
            'Fulano',
            '52998224725',
            'fulano@example.com',
            '5412345678',
        ));
    }

    public function testRejeitaDadosDeDominioInvalidos(): void {
        $gateway = $this->createMock(ClienteGatewayInterface::class);
        $gateway->expects($this->never())->method('inserir');

        $this->expectException(\InvalidArgumentException::class);
        new CriarClienteUseCase($gateway)->executar(new CriarClienteInputDTO(
            'Fulano',
            'cpf-invalido',
            'fulano@example.com',
            '5412345678',
        ));
    }
}

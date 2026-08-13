<?php

declare(strict_types=1);

namespace Tests\Clientes\Infrastructure\Persistence;

use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\Clientes\Infrastructure\Persistence\ClienteGateway;
use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use PHPUnit\Framework\TestCase;

final class ClienteGatewayTest extends TestCase {
    public function testBuscaPorIdMapeiaRegistroParaEntidade(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('buscarPorParametros')
            ->with('clientes', null, ['id' => 1])
            ->willReturn([$this->linha()]);

        $cliente = new ClienteGateway($connection)->buscarPorId(1);

        $this->assertInstanceOf(Cliente::class, $cliente);
        $this->assertSame('Cliente', $cliente->nome());
    }

    public function testRetornaNullQuandoNaoEncontraPorCpfCnpj(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->method('buscarPorParametros')->willReturn([]);

        $cliente = new ClienteGateway($connection)->buscarPorCpfCnpj(new Cpf('52998224725'));

        $this->assertNull($cliente);
    }

    public function testInsereClienteEAdicionaIdGerado(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())->method('inserir')->with(
            'clientes',
            [
                'nome' => 'Cliente',
                'cpf_cnpj' => '52998224725',
                'email' => 'cliente@example.com',
                'telefone' => '5412345678',
            ],
        )->willReturn(7);

        $resultado = new ClienteGateway($connection)->inserir($this->cliente());

        $this->assertSame(7, $resultado->id());
    }

    public function testListaTodosSemFiltro(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())->method('buscarTodos')->with('clientes')->willReturn([$this->linha()]);

        $resultado = new ClienteGateway($connection)->listar();

        $this->assertCount(1, $resultado);
        $this->assertInstanceOf(Cliente::class, $resultado[0]);
    }

    /** @return array<string, mixed> */
    private function linha(): array {
        return [
            'id' => 1,
            'nome' => 'Cliente',
            'cpf_cnpj' => '52998224725',
            'email' => 'cliente@example.com',
            'telefone' => '5412345678',
        ];
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

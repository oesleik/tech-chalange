<?php

declare(strict_types=1);

namespace Tests\Servicos\Infrastructure\Persistence;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Infrastructure\Persistence\ServicoGateway;
use PHPUnit\Framework\TestCase;

final class ServicoGatewayTest extends TestCase {
    public function testBuscarPorIdRetornaServicoQuandoEncontrado(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->method('buscarPorParametros')
            ->with('servicos', null, ['id' => 1])
            ->willReturn([
                ['id' => 1, 'descricao' => 'Troca de óleo', 'valor_unitario' => 49.90],
            ]);

        $gateway = new ServicoGateway($connection);
        $servico = $gateway->buscarPorId(1);

        $this->assertInstanceOf(Servico::class, $servico);
        $this->assertSame(1, $servico->id());
        $this->assertSame('Troca de óleo', $servico->descricao());
    }

    public function testBuscarPorIdRetornaNullQuandoNaoEncontrado(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->method('buscarPorParametros')
            ->with('servicos', null, ['id' => 99])
            ->willReturn([]);

        $gateway = new ServicoGateway($connection);

        $this->assertNull($gateway->buscarPorId(99));
    }

    public function testInserirPersisteEDevolveServicoComId(): void {
        $servico = Servico::criar('Troca de óleo', new ValorUnitario(49.90));

        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('inserir')
            ->with('servicos', [
                'descricao' => 'Troca de óleo',
                'valor_unitario' => 49.90,
            ])
            ->willReturn(1);

        $gateway = new ServicoGateway($connection);
        $resultado = $gateway->inserir($servico);

        $this->assertSame(1, $resultado->id());
        $this->assertSame('Troca de óleo', $resultado->descricao());
    }

    public function testAtualizarPersisteAlteracoesEDevolveServico(): void {
        $servico = Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(59.90));

        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('atualizar')
            ->with(
                'servicos',
                [
                    'descricao' => 'Troca de óleo',
                    'valor_unitario' => 59.90,
                ],
                ['id' => 1],
            );

        $gateway = new ServicoGateway($connection);
        $resultado = $gateway->atualizar($servico);

        $this->assertSame($servico, $resultado);
    }

    public function testListarRetornaTodosOsServicos(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->method('buscarTodos')
            ->with('servicos')
            ->willReturn([
                ['id' => 1, 'descricao' => 'Troca de óleo', 'valor_unitario' => 49.90],
                ['id' => 2, 'descricao' => 'Alinhamento', 'valor_unitario' => 120.0],
            ]);

        $gateway = new ServicoGateway($connection);
        $resultado = $gateway->listar();

        $this->assertCount(2, $resultado);
        $this->assertSame('Troca de óleo', $resultado[0]->descricao());
        $this->assertSame('Alinhamento', $resultado[1]->descricao());
    }

    public function testListarRetornaListaVaziaQuandoNaoHaServicos(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->method('buscarTodos')->with('servicos')->willReturn([]);

        $gateway = new ServicoGateway($connection);

        $this->assertSame([], $gateway->listar());
    }
}

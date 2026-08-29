<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Infrastructure\Persistence;

use App\Core\AppDatabase;
use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use App\Estoque\Infrastructure\Persistence\EstoqueGateway;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class EstoqueGatewayTest extends TestCase {
    public function testPecaExisteRetornaTrueQuandoRegistroExiste(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('buscarPorParametros')
            ->with('pecas', ['id'], ['id' => 10])
            ->willReturn([['id' => 10]]);

        $gateway = new EstoqueGateway($connection, $this->createMock(AppDatabase::class));

        $this->assertTrue($gateway->pecaExiste(10));
    }

    public function testPecaExisteRetornaFalseQuandoRegistroNaoExiste(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('buscarPorParametros')
            ->with('pecas', ['id'], ['id' => 99])
            ->willReturn([]);

        $gateway = new EstoqueGateway($connection, $this->createMock(AppDatabase::class));

        $this->assertFalse($gateway->pecaExiste(99));
    }

    public function testCalcularEstoqueAtualRetornaValorCalculadoPeloBanco(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([
                'entrada' => TipoLancamentoEnum::ENTRADA->value,
                'baixa' => TipoLancamentoEnum::BAIXA->value,
                'id_peca' => 10,
            ]);
        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(['estoque_atual' => '17']);

        $db = $this->createMock(AppDatabase::class);
        $db->expects($this->once())->method('prepare')->with($this->stringContains('FROM estoque'))->willReturn($stmt);

        $gateway = new EstoqueGateway($this->createMock(DbConnectionInterface::class), $db);

        $this->assertSame(17, $gateway->calcularEstoqueAtual(10));
    }

    public function testCalcularEstoqueAtualRetornaZeroQuandoBancoRetornaZero(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetch')->willReturn(['estoque_atual' => null]);

        $db = $this->createMock(AppDatabase::class);
        $db->method('prepare')->willReturn($stmt);

        $gateway = new EstoqueGateway($this->createMock(DbConnectionInterface::class), $db);

        $this->assertSame(0, $gateway->calcularEstoqueAtual(123));
    }

    public function testInserirLancamentoPersisteDadosEReconstroiEntidade(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('inserir')
            ->with('estoque', [
                'id_peca' => 10,
                'quantidade' => 5,
                'tipo_lancamento' => 'entrada',
            ])
            ->willReturn(42);

        $gateway = new EstoqueGateway($connection, $this->createMock(AppDatabase::class));
        $resultado = $gateway->inserirLancamento(10, 5, TipoLancamentoEnum::ENTRADA);

        $this->assertInstanceOf(LancamentoEstoque::class, $resultado);
        $this->assertSame(42, $resultado->id());
        $this->assertSame(10, $resultado->pecaId());
        $this->assertSame(5, $resultado->quantidade());
        $this->assertSame(TipoLancamentoEnum::ENTRADA, $resultado->tipo());
    }
}

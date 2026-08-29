<?php

declare(strict_types=1);

namespace Tests\Peca\Infrastructure\Persistence;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;
use App\Peca\Infrastructure\Persistence\PecaGateway;
use PHPUnit\Framework\TestCase;

final class PecaGatewayTest extends TestCase {
    public function testBuscaPorIdMapeiaRegistroParaEntidade(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('buscarPorParametros')
            ->with('pecas', null, ['id' => 1])
            ->willReturn([$this->linha()]);

        $peca = new PecaGateway($connection)->buscarPorId(1);

        $this->assertInstanceOf(Peca::class, $peca);
        $this->assertSame('Filtro de óleo', $peca->descricao());
    }

    public function testRetornaNullQuandoNaoEncontraPorId(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->method('buscarPorParametros')->willReturn([]);

        $peca = new PecaGateway($connection)->buscarPorId(999);

        $this->assertNull($peca);
    }

    public function testInserePecaEAdicionaIdGerado(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())->method('inserir')->with(
            'pecas',
            [
                'descricao' => 'Filtro de óleo',
                'valor_unitario' => 49.9,
            ],
        )->willReturn(3);

        $resultado = new PecaGateway($connection)->inserir($this->peca());

        $this->assertSame(3, $resultado->id());
    }

    public function testAtualizaPecaERetornaEntidade(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())->method('atualizar')->with(
            'pecas',
            [
                'descricao' => 'Filtro de óleo',
                'valor_unitario' => 49.9,
            ],
            ['id' => 1],
        );

        $peca = $this->peca()->comId(1);
        $resultado = new PecaGateway($connection)->atualizar($peca);

        $this->assertSame($peca, $resultado);
    }

    public function testListaTodasAsPecas(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('buscarTodos')
            ->with('pecas')
            ->willReturn([$this->linha()]);

        $resultado = new PecaGateway($connection)->listar();

        $this->assertCount(1, $resultado);
        $this->assertInstanceOf(Peca::class, $resultado[0]);
    }

    /** @return array<string, mixed> */
    private function linha(): array {
        return [
            'id' => 1,
            'descricao' => 'Filtro de óleo',
            'valor_unitario' => 49.90,
        ];
    }

    private function peca(): Peca {
        return Peca::criar('Filtro de óleo', new ValorUnitario(49.90));
    }
}

<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Estoque\Repository\EstoqueRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EstoqueRepositoryTest extends TestCase {
    private AppDatabase&MockObject $db;
    private EstoqueRepository $repository;

    protected function setUp(): void {
        $this->db         = $this->createMock(AppDatabase::class);
        $this->repository = new EstoqueRepository($this->db);
    }

    public function testRegistrarEntradaComSucesso(): void {
        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('execute')->willReturn(true);
        $stmtSelect->method('fetch')->willReturn(['id' => 1, 'descricao' => 'Filtro de óleo']);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $this->db->method('prepare')->willReturnOnConsecutiveCalls($stmtSelect, $stmtInsert);
        $this->db->method('lastInsertId')->willReturn('1');

        $result = $this->repository->registrarEntrada(1, 10);

        $this->assertSame(1, $result['id']);
        $this->assertSame(1, $result['id_peca']);
        $this->assertSame('Filtro de óleo', $result['peca']);
        $this->assertSame(10, $result['quantidade']);
        $this->assertSame('entrada', $result['tipo_lancamento']);
    }

    public function testRegistrarEntradaLancaExcecaoQuandoPecaNaoEncontrada(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $this->db->method('prepare')->willReturn($stmt);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Peça com ID 999 não encontrada.');

        $this->repository->registrarEntrada(999, 10);
    }

    public function testConsultarEstoquePorPecaComSucesso(): void {
        $stmtPeca = $this->createMock(\PDOStatement::class);
        $stmtPeca->method('fetch')->willReturn([
            'id'             => 1,
            'descricao'      => 'Filtro de óleo',
            'valor_unitario' => '29.90',
        ]);

        $stmtEstoque = $this->createMock(\PDOStatement::class);
        $stmtEstoque->method('fetch')->willReturn(['estoque_atual' => '10']);

        $this->db->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtPeca, $stmtEstoque);

        $result = $this->repository->consultarEstoquePorPeca(1);

        $this->assertSame(1, $result['id_peca']);
        $this->assertSame('Filtro de óleo', $result['descricao']);
        $this->assertSame(29.90, $result['valor_unitario']);
        $this->assertSame(10, $result['estoque_atual']);
    }

    public function testConsultarEstoqueLancaExcecaoQuandoPecaNaoEncontrada(): void {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);
        $this->db->method('prepare')->willReturn($stmt);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(404);

        $this->repository->consultarEstoquePorPeca(999);
    }
}

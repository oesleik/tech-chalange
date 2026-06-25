<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Estoque\Model\TipoLancamentoEstoqueEnum;
use App\Estoque\Service\EstoqueInsuficienteException;
use App\Estoque\Service\EstoqueService;
use App\Estoque\Service\PecaNaoEncontradaException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EstoqueServiceTest extends TestCase {
    private AppDatabase&MockObject $db;
    private EstoqueService $service;

    protected function setUp(): void {
        $this->db         = $this->createMock(AppDatabase::class);
        $this->service = new EstoqueService($this->db);
    }

    public function testRegistrarEntradaComSucesso(): void {
        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('execute')->willReturn(true);
        $stmtSelect->method('fetch')->willReturn(['id' => 123, 'descricao' => 'Filtro de óleo']);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $this->db->method('prepare')->willReturnOnConsecutiveCalls($stmtSelect, $stmtInsert);
        $this->db->method('lastInsertId')->willReturn('456');

        $result = $this->service->registrarEntrada(123, 10);

        $this->assertEquals(456, $result->getId());
        $this->assertEquals(123, $result->getIdPeca());
        $this->assertEquals(10, $result->getQuantidade());
        $this->assertEquals(TipoLancamentoEstoqueEnum::ENTRADA, $result->getTipoLancamento());
    }

    public function testRegistrarEntradaLancaExcecaoQuandoPecaNaoEncontrada(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $this->db->method('prepare')->willReturn($stmt);

        $this->expectException(PecaNaoEncontradaException::class);
        $this->service->registrarEntrada(999, 10);
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

        $this->db->method('prepare')->willReturnOnConsecutiveCalls($stmtPeca, $stmtEstoque);
        $result = $this->service->consultarEstoquePorPeca(1);

        $this->assertSame(1, $result->getIdPeca());
        $this->assertSame(10, $result->getEstoqueAtual());
    }

    public function testConsultarEstoqueLancaExcecaoQuandoPecaNaoEncontrada(): void {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);
        $this->db->method('prepare')->willReturn($stmt);

        $this->expectException(PecaNaoEncontradaException::class);
        $this->service->consultarEstoquePorPeca(999);
    }

    public function testRegistrarBaixaComSucesso(): void {
        $stmtPeca = $this->createMock(PDOStatement::class);
        $stmtPeca->method('fetch')->willReturn(['id' => 123, 'descricao' => 'Filtro de óleo']);

        $stmtEstoqueAtual = $this->createMock(PDOStatement::class);
        $stmtEstoqueAtual->method('fetch')->willReturn(['estoque_atual' => '10']);

        $stmtInsert = $this->createMock(PDOStatement::class);

        $this->db->method('prepare')->willReturnOnConsecutiveCalls($stmtPeca, $stmtEstoqueAtual, $stmtInsert);
        $this->db->method('lastInsertId')->willReturn('2');

        $result = $this->service->registrarBaixa(123, 4);

        $this->assertEquals(2, $result->getId());
        $this->assertEquals(123, $result->getIdPeca());
        $this->assertEquals(4, $result->getQuantidade());
        $this->assertEquals(TipoLancamentoEstoqueEnum::BAIXA, $result->getTipoLancamento());
    }

    public function testRegistrarBaixaLancaExcecaoQuandoPecaNaoEncontrada(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);
        $this->db->method('prepare')->willReturn($stmt);

        $this->expectException(PecaNaoEncontradaException::class);
        $this->service->registrarBaixa(999, 5);
    }

    public function testRegistrarBaixaLancaExcecaoQuandoEstoqueInsuficiente(): void {
        $stmtPeca = $this->createMock(PDOStatement::class);
        $stmtPeca->method('fetch')->willReturn(['id' => 1, 'descricao' => 'Filtro de óleo']);

        $stmtEstoqueAtual = $this->createMock(PDOStatement::class);
        $stmtEstoqueAtual->method('fetch')->willReturn(['estoque_atual' => '3']);

        $this->db->method('prepare')->willReturnOnConsecutiveCalls($stmtPeca, $stmtEstoqueAtual);

        $this->expectException(EstoqueInsuficienteException::class);
        $this->service->registrarBaixa(1, 10);
    }
}

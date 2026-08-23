<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Infrastructure\Persistence;

use App\Core\AppDatabase;
use App\Core\Database\TransactionHandler;
use App\Core\Database\TransactionInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Infrastructure\Persistence\ItensOrdemServicoGateway;
use DateTime;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class ItensOrdemServicoGatewayTest extends TestCase {
    public function testBuscarPecasPorOrdemServicoRetornaEntidades(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1]);
        $stmt->method('fetchObject')->willReturnOnConsecutiveCalls(
            (object) ['id_peca' => 5, 'quantidade' => 2, 'valor_unitario' => 10.0],
            false,
        );

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->method('prepare')->willReturn($stmt);

        $gateway = $this->criarGateway($pdo);
        $pecas = $gateway->buscarPecasPorOrdemServico(1);

        $this->assertCount(1, $pecas);
        $this->assertInstanceOf(PecaOrdemServico::class, $pecas[0]);
        $this->assertSame(5, $pecas[0]->idPeca());
    }

    public function testBuscarServicosPorOrdemServicoRetornaEntidades(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1]);
        $stmt->method('fetchObject')->willReturnOnConsecutiveCalls(
            (object) ['id_servico' => 8, 'quantidade' => 3, 'valor_unitario' => 20.0],
            false,
        );

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->method('prepare')->willReturn($stmt);

        $gateway = $this->criarGateway($pdo);
        $servicos = $gateway->buscarServicosPorOrdemServico(1);

        $this->assertCount(1, $servicos);
        $this->assertInstanceOf(ServicoOrdemServico::class, $servicos[0]);
        $this->assertSame(8, $servicos[0]->idServico());
    }

    public function testAdicionarPecasInsereERecalculaValorTotal(): void {
        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt->expects($this->once())->method('execute')->with([1, 5, 2]);

        $somaStmt = $this->createMock(PDOStatement::class);
        $somaStmt->method('execute');
        $somaStmt->method('fetchAll')->willReturn([(object) ['quantidade' => 2, 'valor_unitario' => 10.0]]);

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($insertStmt, $somaStmt, $somaStmt);

        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->expects($this->once())->method('atualizarValorTotal')->with(1, 40.0);

        $transactionHandler = $this->createMock(TransactionHandler::class);
        $transactionHandler->method('beginTransaction')->willReturn($this->createMock(TransactionInterface::class));

        $gateway = new ItensOrdemServicoGateway($pdo, $transactionHandler, $ordemServicoGateway);
        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime());

        $gateway->adicionarPecas($os, [new PecaOrdemServico(5, 2, 10.0)]);
    }

    private function criarGateway(AppDatabase $pdo): ItensOrdemServicoGateway {
        $transactionHandler = $this->createMock(TransactionHandler::class);
        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);

        return new ItensOrdemServicoGateway($pdo, $transactionHandler, $ordemServicoGateway);
    }
}

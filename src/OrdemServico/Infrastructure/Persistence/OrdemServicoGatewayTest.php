<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Infrastructure\Persistence;

use App\Core\AppDatabase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\ValueObject\FiltroOrdemServico;
use App\OrdemServico\Infrastructure\Persistence\OrdemServicoGateway;
use DateTime;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class OrdemServicoGatewayTest extends TestCase {
    public function testBuscarPorIdMapeiaRegistroParaEntidade(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1]);
        $stmt->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($this->linha());

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $os = new OrdemServicoGateway($pdo)->buscarPorId(1);

        $this->assertInstanceOf(OrdemServico::class, $os);
        $this->assertSame(1, $os->id());
    }

    public function testBuscarPorIdRetornaNullQuandoNaoEncontrado(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertNull(new OrdemServicoGateway($pdo)->buscarPorId(99));
    }

    public function testListarAplicaFiltrosELimite(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([SituacaoOrdemServicoEnum::APROVADA->value, 10, 20]);
        $stmt->method('fetch')->willReturnOnConsecutiveCalls($this->linha(), false);

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('LIMIT 1'))
            ->willReturn($stmt);

        $resultado = new OrdemServicoGateway($pdo)->listar(new FiltroOrdemServico(
            situacao: SituacaoOrdemServicoEnum::APROVADA,
            idCliente: 10,
            idVeiculo: 20,
            limit: 1,
        ));

        $this->assertCount(1, $resultado);
    }

    public function testInserirRetornaEntidadeComIdGerado(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute');

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->method('prepare')->willReturn($stmt);
        $pdo->method('lastInsertId')->willReturn('7');

        $os = new OrdemServico(0, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime());
        $resultado = new OrdemServicoGateway($pdo)->inserir($os);

        $this->assertSame(7, $resultado->id());
    }

    public function testAtualizarSituacaoIncluiDataAprovacaoQuandoPresente(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(fn(array $params) => count($params) === 3));

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('data_aprovacao'))
            ->willReturn($stmt);

        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::APROVADA, 0, new DateTime(), new DateTime());
        new OrdemServicoGateway($pdo)->atualizarSituacao($os);
    }

    public function testAtualizarValorTotalExecutaUpdate(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([99.9, 1]);

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        new OrdemServicoGateway($pdo)->atualizarValorTotal(1, 99.9);
    }

    public function testObterProximaNaFilaPriorizaAprovadaSobreRecebida(): void {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn($this->linha());

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $os = new OrdemServicoGateway($pdo)->obterProximaNaFila();

        $this->assertNotNull($os);
    }

    public function testObterProximaNaFilaCaiParaRecebidaQuandoNaoHaAprovada(): void {
        $stmtAprovada = $this->createMock(PDOStatement::class);
        $stmtAprovada->method('fetch')->willReturn(false);

        $stmtRecebida = $this->createMock(PDOStatement::class);
        $stmtRecebida->method('fetch')->willReturn($this->linha());

        $pdo = $this->createMock(AppDatabase::class);
        $pdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtAprovada, $stmtRecebida);

        $os = new OrdemServicoGateway($pdo)->obterProximaNaFila();

        $this->assertNotNull($os);
    }

    /** @return array<string, mixed> */
    private function linha(): array {
        return [
            'id' => 1,
            'id_cliente' => 10,
            'id_veiculo' => 20,
            'situacao' => SituacaoOrdemServicoEnum::RECEBIDA->value,
            'valor_total' => 0,
            'data_solicitacao' => '2026-01-01 10:00:00',
            'data_aprovacao' => null,
        ];
    }
}

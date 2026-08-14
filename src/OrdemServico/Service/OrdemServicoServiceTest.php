<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\OrdemServico\Model\FiltroOrdemServico;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\OrdemServicoService;
use App\OrdemServico\Service\SituacaoBloqueadaException;
use PHPUnit\Framework\TestCase;

class OrdemServicoServiceTest extends TestCase {
    public function testListarOrdensServico(): void {
        $mocks = [
            (object) [
                "id" => "123",
                "id_cliente" => "456",
                "id_veiculo" => "789",
                "situacao" => SituacaoOrdemServicoEnum::APROVADA->value,
                "valor_total" => "45.52",
                "data_solicitacao" => "2026-05-28 12:13:20",
                "data_aprovacao" => "2026-06-02 15:05:45",
            ],
            (object) [
                "id" => "456",
                "id_cliente" => "222",
                "id_veiculo" => "333",
                "situacao" => SituacaoOrdemServicoEnum::EM_DIAGNOSTICO->value,
                "valor_total" => "0",
                "data_solicitacao" => "2026-05-30 08:38:05",
                "data_aprovacao" => null,
            ],
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(...[...$mocks, false]);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new OrdemServicoService($dbStub);
        $res = $service->listarOrdensServico(new FiltroOrdemServico());
        $this->assertCount(2, $res);

        $this->assertInstanceOf(OrdemServicoModel::class, $res[0]);
        $this->assertEquals($mocks[0]->id, $res[0]->getId());
        $this->assertEquals($mocks[0]->id_cliente, $res[0]->getIdCliente());
        $this->assertEquals($mocks[0]->id_veiculo, $res[0]->getIdVeiculo());
        $this->assertEquals($mocks[0]->situacao, $res[0]->getSituacao()->value);
        $this->assertEquals($mocks[0]->valor_total, $res[0]->getValorTotal());
        $this->assertEquals($mocks[0]->data_solicitacao, $res[0]->getDataSolicitacao()->format("Y-m-d H:i:s"));
        $this->assertEquals($mocks[0]->data_aprovacao, $res[0]->getDataAprovacao()->format("Y-m-d H:i:s"));

        $this->assertInstanceOf(OrdemServicoModel::class, $res[1]);
        $this->assertEquals($mocks[1]->id, $res[1]->getId());
        $this->assertEquals($mocks[1]->id_cliente, $res[1]->getIdCliente());
        $this->assertEquals($mocks[1]->id_veiculo, $res[1]->getIdVeiculo());
        $this->assertEquals($mocks[1]->situacao, $res[1]->getSituacao()->value);
        $this->assertEquals($mocks[1]->valor_total, $res[1]->getValorTotal());
        $this->assertEquals($mocks[1]->data_solicitacao, $res[1]->getDataSolicitacao()->format("Y-m-d H:i:s"));
        $this->assertNull($res[1]->getDataAprovacao());
    }

    public function testListarOrdensServicoComFiltros(): void {
        $mocks = [
            (object) [
                "id" => "123",
                "id_cliente" => "456",
                "id_veiculo" => "789",
                "situacao" => SituacaoOrdemServicoEnum::APROVADA->value,
                "valor_total" => "45.52",
                "data_solicitacao" => "2026-05-28 12:13:20",
                "data_aprovacao" => "2026-06-02 15:05:45",
            ],
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(...[...$mocks, false]);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new OrdemServicoService($dbStub);
        $res = $service->listarOrdensServico(new FiltroOrdemServico(
            situacao: SituacaoOrdemServicoEnum::APROVADA,
            idCliente: 456,
            idVeiculo: 789,
            limit: 10
        ));
        $this->assertCount(1, $res);

        $this->assertInstanceOf(OrdemServicoModel::class, $res[0]);
        $this->assertEquals($mocks[0]->id, $res[0]->getId());
        $this->assertEquals($mocks[0]->id_cliente, $res[0]->getIdCliente());
        $this->assertEquals($mocks[0]->id_veiculo, $res[0]->getIdVeiculo());
        $this->assertEquals($mocks[0]->situacao, $res[0]->getSituacao()->value);
        $this->assertEquals($mocks[0]->valor_total, $res[0]->getValorTotal());
        $this->assertEquals($mocks[0]->data_solicitacao, $res[0]->getDataSolicitacao()->format("Y-m-d H:i:s"));
        $this->assertEquals($mocks[0]->data_aprovacao, $res[0]->getDataAprovacao()->format("Y-m-d H:i:s"));
    }

    public function testObterOrdemServicoPorId(): void {
        $mock = (object) [
            "id" => "123",
            "id_cliente" => "456",
            "id_veiculo" => "789",
            "situacao" => SituacaoOrdemServicoEnum::APROVADA->value,
            "valor_total" => "45.52",
            "data_solicitacao" => "2026-05-28 12:13:20",
            "data_aprovacao" => "2026-06-02 15:05:45",
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturn($mock);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new OrdemServicoService($dbStub);
        $res = $service->obterOrdemServicoPorId(123);

        $this->assertInstanceOf(OrdemServicoModel::class, $res);
        $this->assertEquals($mock->id, $res->getId());
        $this->assertEquals($mock->id_cliente, $res->getIdCliente());
        $this->assertEquals($mock->id_veiculo, $res->getIdVeiculo());
        $this->assertEquals($mock->situacao, $res->getSituacao()->value);
        $this->assertEquals($mock->valor_total, $res->getValorTotal());
        $this->assertEquals($mock->data_solicitacao, $res->getDataSolicitacao()->format("Y-m-d H:i:s"));
        $this->assertEquals($mock->data_aprovacao, $res->getDataAprovacao()->format("Y-m-d H:i:s"));
    }

    public function testObterProximaOrdemServicoNaFila(): void {
        $mock = (object) [
            "id" => "123",
            "id_cliente" => "456",
            "id_veiculo" => "789",
            "situacao" => SituacaoOrdemServicoEnum::RECEBIDA->value,
            "valor_total" => "45.52",
            "data_solicitacao" => "2026-05-28 12:13:20",
            "data_aprovacao" => null,
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(false, $mock);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new OrdemServicoService($dbStub);
        $res = $service->obterProximaOrdemServicoNaFila();

        $this->assertInstanceOf(OrdemServicoModel::class, $res);
        $this->assertEquals($mock->id, $res->getId());
        $this->assertEquals($mock->id_cliente, $res->getIdCliente());
        $this->assertEquals($mock->id_veiculo, $res->getIdVeiculo());
        $this->assertEquals($mock->situacao, $res->getSituacao()->value);
    }

    public function testCriarOrdemServico(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);
        $dbStub->method("lastInsertId")->willReturn("123");

        $ordemServico = new OrdemServicoModel(
            id: 0,
            idCliente: 456,
            idVeiculo: 789,
            situacao: SituacaoOrdemServicoEnum::RECEBIDA,
            valorTotal: 0,
            dataSolicitacao: new DateTime(),
        );

        $service = new OrdemServicoService($dbStub);
        $res = $service->criarOrdemServico($ordemServico);

        $this->assertInstanceOf(OrdemServicoModel::class, $res);
        $this->assertEquals(123, $res->getId());
        $this->assertEquals($ordemServico->getIdCliente(), $res->getIdCliente());
        $this->assertEquals($ordemServico->getIdVeiculo(), $res->getIdVeiculo());
        $this->assertEquals($ordemServico->getSituacao()->value, $res->getSituacao()->value);
        $this->assertEquals($ordemServico->getValorTotal(), $res->getValorTotal());
        $this->assertEquals($ordemServico->getDataSolicitacao()->format("Y-m-d H:i:s"), $res->getDataSolicitacao()->format("Y-m-d H:i:s"));
        $this->assertNull($res->getDataAprovacao());
    }

    public function testAtualizarSituacao(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $ordemServico = new OrdemServicoModel(
            id: 123,
            idCliente: 456,
            idVeiculo: 789,
            situacao: SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO,
            valorTotal: 0,
            dataSolicitacao: new DateTime(),
        );

        $service = new OrdemServicoService($dbStub);

        // Mesma situação
        $res = $service->atualizarSituacao($ordemServico, SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO);
        $this->assertSame($ordemServico, $res);

        $res = $service->atualizarSituacao($ordemServico, SituacaoOrdemServicoEnum::APROVADA);

        $this->assertNotSame($ordemServico, $res);
        $this->assertEquals($ordemServico->getId(), $res->getId());
        $this->assertEquals($ordemServico->getIdCliente(), $res->getIdCliente());
        $this->assertEquals($ordemServico->getIdVeiculo(), $res->getIdVeiculo());
        $this->assertSame(SituacaoOrdemServicoEnum::APROVADA, $res->getSituacao());
        $this->assertEquals($ordemServico->getValorTotal(), $res->getValorTotal());
        $this->assertEquals($ordemServico->getDataSolicitacao()->format("Y-m-d H:i:s"), $res->getDataSolicitacao()->format("Y-m-d H:i:s"));
        $this->assertNotNull($res->getDataAprovacao());
        $this->assertGreaterThanOrEqual($ordemServico->getDataSolicitacao()->getTimestamp(), $res->getDataAprovacao()->getTimestamp());
    }

    public function testAtualizarSituacaoIdOsNaoInformado(): void {
        $ordemServico = new OrdemServicoModel(
            id: 0,
            idCliente: 456,
            idVeiculo: 789,
            situacao: SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO,
            valorTotal: 0,
            dataSolicitacao: new DateTime(),
        );

        $dbStub = $this->createStub(AppDatabase::class);
        $service = new OrdemServicoService($dbStub);

        $this->expectException(InvalidArgumentException::class);
        $service->atualizarSituacao($ordemServico, SituacaoOrdemServicoEnum::APROVADA);
    }

    public function testAtualizarSituacaoBloqueada(): void {
        $ordemServico = new OrdemServicoModel(
            id: 123,
            idCliente: 456,
            idVeiculo: 789,
            situacao: SituacaoOrdemServicoEnum::RECEBIDA,
            valorTotal: 0,
            dataSolicitacao: new DateTime(),
        );

        $dbStub = $this->createStub(AppDatabase::class);
        $service = new OrdemServicoService($dbStub);

        $this->expectException(SituacaoBloqueadaException::class);
        $service->atualizarSituacao($ordemServico, SituacaoOrdemServicoEnum::APROVADA);
    }

    public function testAtualizarValorTotal(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $this->expectNotToPerformAssertions();
        $service = new OrdemServicoService($dbStub);
        $service->atualizarValorTotal(123, 45.86);
    }
}

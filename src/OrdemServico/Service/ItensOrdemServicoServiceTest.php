<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Core\Database\FakeTransaction;
use App\Core\Database\TransactionHandler;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use App\OrdemServico\Model\ServicoOrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\CalculoValorTotalOrdemServicoService;
use App\OrdemServico\Service\ItensOrdemServicoService;
use PHPUnit\Framework\TestCase;

class ItensOrdemServicoServiceTest extends TestCase {
    private function createService(
        AppDatabase $dbStub,
        ?CalculoValorTotalOrdemServicoService $attValorService = null,
    ): ItensOrdemServicoService {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();
        $attValorService ??= $container->get(CalculoValorTotalOrdemServicoService::class);

        $tsxStub = $this->createStub(TransactionHandler::class);
        $tsxStub->method("beginTransaction")->willReturn(new FakeTransaction());

        return $container->make(ItensOrdemServicoService::class, [
            "pdo" => $dbStub,
            "transactionHandler" => $tsxStub,
            "atualizarValorTotalService" => $attValorService,
        ]);
    }

    public function testObterPecasPorIdOrdemServico(): void {
        $mocks = [
            (object) [
                "id_peca" => "123",
                "quantidade" => "10",
                "valor_unitario" => "45.52",
            ],
            (object) [
                "id_peca" => "456",
                "quantidade" => "2",
                "valor_unitario" => "80",
            ],
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(...[...$mocks, false]);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = $this->createService($dbStub);
        $res = $service->obterPecasPorIdOrdemServico(123);
        $this->assertCount(2, $res);

        $this->assertInstanceOf(PecaOrdemServicoModel::class, $res[0]);
        $this->assertEquals($mocks[0]->id_peca, $res[0]->getIdPeca());
        $this->assertEquals($mocks[0]->quantidade, $res[0]->getQuantidade());
        $this->assertEquals($mocks[0]->valor_unitario, $res[0]->getValorUnitario());
        $this->assertEquals(round(intval($mocks[0]->quantidade) * floatval($mocks[0]->valor_unitario), 2), $res[0]->getSubtotal());

        $this->assertInstanceOf(PecaOrdemServicoModel::class, $res[1]);
        $this->assertEquals($mocks[1]->id_peca, $res[1]->getIdPeca());
        $this->assertEquals($mocks[1]->quantidade, $res[1]->getQuantidade());
        $this->assertEquals($mocks[1]->valor_unitario, $res[1]->getValorUnitario());
        $this->assertEquals(round(intval($mocks[1]->quantidade) * floatval($mocks[1]->valor_unitario), 2), $res[1]->getSubtotal());
    }

    public function testAtualizarPecas(): void {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->exactly(3))->method("execute");

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtMock);

        $attValorMock = $this->createMock(CalculoValorTotalOrdemServicoService::class);
        $attValorMock->expects($this->exactly(1))->method("calcularEAtualizar")->with(123);

        $ordemServico = new OrdemServicoModel(
            id: 123,
            idCliente: 456,
            idVeiculo: 789,
            situacao: SituacaoOrdemServicoEnum::EM_DIAGNOSTICO,
            valorTotal: 126.42,
            dataSolicitacao: new DateTime(),
        );

        $pecas = [
            new PecaOrdemServicoModel(
                idPeca: 111,
                quantidade: 10,
                valorUnitario: 80.90,
            ),
            new PecaOrdemServicoModel(
                idPeca: 222,
                quantidade: 2,
                valorUnitario: 45.52,
            ),
        ];

        $service = $this->createService($dbStub, $attValorMock);
        $service->atualizarPecas($ordemServico, $pecas);
    }

    public function testObterServicosPorIdOrdemServico(): void {
        $mocks = [
            (object) [
                "id_servico" => "123",
                "quantidade" => "10",
                "valor_unitario" => "45.52",
            ],
            (object) [
                "id_servico" => "456",
                "quantidade" => "2",
                "valor_unitario" => "80",
            ],
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(...[...$mocks, false]);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = $this->createService($dbStub);
        $res = $service->obterServicosPorIdOrdemServico(123);
        $this->assertCount(2, $res);

        $this->assertInstanceOf(ServicoOrdemServicoModel::class, $res[0]);
        $this->assertEquals($mocks[0]->id_servico, $res[0]->getIdServico());
        $this->assertEquals($mocks[0]->quantidade, $res[0]->getQuantidade());
        $this->assertEquals($mocks[0]->valor_unitario, $res[0]->getValorUnitario());
        $this->assertEquals(round(intval($mocks[0]->quantidade) * floatval($mocks[0]->valor_unitario), 2), $res[0]->getSubtotal());

        $this->assertInstanceOf(ServicoOrdemServicoModel::class, $res[1]);
        $this->assertEquals($mocks[1]->id_servico, $res[1]->getIdServico());
        $this->assertEquals($mocks[1]->quantidade, $res[1]->getQuantidade());
        $this->assertEquals($mocks[1]->valor_unitario, $res[1]->getValorUnitario());
        $this->assertEquals(round(intval($mocks[1]->quantidade) * floatval($mocks[1]->valor_unitario), 2), $res[1]->getSubtotal());
    }

    public function testAtualizarServicos(): void {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->exactly(3))->method("execute");

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtMock);

        $attValorMock = $this->createMock(CalculoValorTotalOrdemServicoService::class);
        $attValorMock->expects($this->exactly(1))->method("calcularEAtualizar")->with(123);

        $ordemServico = new OrdemServicoModel(
            id: 123,
            idCliente: 456,
            idVeiculo: 789,
            situacao: SituacaoOrdemServicoEnum::EM_DIAGNOSTICO,
            valorTotal: 126.42,
            dataSolicitacao: new DateTime(),
        );

        $servicos = [
            new ServicoOrdemServicoModel(
                idServico: 111,
                quantidade: 10,
                valorUnitario: 80.90,
            ),
            new ServicoOrdemServicoModel(
                idServico: 222,
                quantidade: 2,
                valorUnitario: 45.52,
            ),
        ];

        $service = $this->createService($dbStub, $attValorMock);
        $service->atualizarServicos($ordemServico, $servicos);
    }
}

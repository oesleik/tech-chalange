<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Servicos\Model\ServicoModel;
use App\Servicos\Service\ServicosService;
use PHPUnit\Framework\TestCase;

class ServicosServiceTest extends TestCase {
    public function testListarServicos(): void {
        $mocks = [
            (object) [
                "id" => "123",
                "descricao" => "Revisão",
                "valorUnitario" => "150",
            ],
            (object) [
                "id" => "456",
                "descricao" => "Diagnóstico",
                "valor_unitario" => "80",
            ],
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("getIterator")->willReturn(new ArrayIterator($mocks));

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("query")->willReturn($stmtStub);

        $service = new ServicosService($dbStub);
        $res = $service->listarServicos();
        $this->assertCount(2, $res);

        $this->assertInstanceOf(ServicoModel::class, $res[0]);
        $this->assertEquals($mocks[0]->id, $res[0]->getId());
        $this->assertEquals($mocks[0]->descricao, $res[0]->getDescricao());
        $this->assertEquals($mocks[0]->valor_unitario, $res[0]->getValorUnitario());

        $this->assertInstanceOf(ServicoModel::class, $res[1]);
        $this->assertEquals($mocks[1]->id, $res[1]->getId());
        $this->assertEquals($mocks[1]->descricao, $res[1]->getDescricao());
        $this->assertEquals($mocks[1]->valor_unitario, $res[1]->getValorUnitario());
    }

    public function testObterServicoPorId(): void {
        $mock = (object) [
            "id" => "123",
            "descricao" => "Revisão",
            "valorUnitario" => "150",
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls($mock, false);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new ServicosService($dbStub);
        $res = $service->obterServicoPorId(intval($mock->id));

        $this->assertInstanceOf(ServicoModel::class, $res);
        $this->assertEquals($mock->id, $res->getId());
        $this->assertEquals($mock->descricao, $res->getDescricao());
        $this->assertEquals($mock->valor_unitario, $res->getValorUnitario());

        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(false);
        $res = $service->obterServicoPorId(789);
        $this->assertNull($res);
    }

    public function testCriarServico(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);
        $dbStub->method("lastInsertId")->willReturn("123");

        $servico = new ServicoModel(
            id: 0,
            descricao: "Revisão",
            valorUnitario: 150,
        );

        $service = new ServicosService($dbStub);
        $res = $service->criarServico($servico);

        $this->assertInstanceOf(ServicoModel::class, $res);
        $this->assertNotSame($servico, $res);
        $this->assertEquals(123, $res->getId());
        $this->assertEquals($servico->getDescricao(), $res->getDescricao());
        $this->assertEquals($servico->getValorUnitario(), $res->getValorUnitario());
    }

    public function testAtualizarServico(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $servico = new ServicoModel(
            id: 123,
            descricao: "Revisão",
            valorUnitario: 150,
        );

        $service = new ServicosService($dbStub);
        $service->atualizarServico($servico);
        $this->expectNotToPerformAssertions();
    }

}

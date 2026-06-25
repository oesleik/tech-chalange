<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Peca\Model\PecaModel;
use App\Peca\Service\PecaService;
use PHPUnit\Framework\TestCase;

class PecaServiceTest extends TestCase {
    public function testListarPecas(): void {
        $mocks = [
            (object) [
                "id" => "123",
                "descricao" => "Vela",
                "valor_unitario" => "22.75",
            ],
            (object) [
                "id" => "456",
                "descricao" => "Correia",
                "valor_unitario" => "145",
            ],
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("getIterator")->willReturn(new ArrayIterator($mocks));

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("query")->willReturn($stmtStub);

        $service = new PecaService($dbStub);
        $res = $service->listarPecas();
        $this->assertCount(2, $res);

        $this->assertInstanceOf(PecaModel::class, $res[0]);
        $this->assertEquals($mocks[0]->id, $res[0]->getId());
        $this->assertEquals($mocks[0]->descricao, $res[0]->getDescricao());
        $this->assertEquals($mocks[0]->valor_unitario, $res[0]->getValorUnitario());

        $this->assertInstanceOf(PecaModel::class, $res[1]);
        $this->assertEquals($mocks[1]->id, $res[1]->getId());
        $this->assertEquals($mocks[1]->descricao, $res[1]->getDescricao());
        $this->assertEquals($mocks[1]->valor_unitario, $res[1]->getValorUnitario());
    }

    public function testObterPecaPorId(): void {
        $mock = (object) [
            "id" => "123",
            "descricao" => "Vela",
            "valor_unitario" => "22.75",
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls($mock, false);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new PecaService($dbStub);
        $res = $service->obterPecaPorId(intval($mock->id));

        $this->assertInstanceOf(PecaModel::class, $res);
        $this->assertEquals($mock->id, $res->getId());
        $this->assertEquals($mock->descricao, $res->getDescricao());
        $this->assertEquals($mock->valor_unitario, $res->getValorUnitario());

        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(false);
        $res = $service->obterPecaPorId(789);
        $this->assertNull($res);
    }

    public function testCriarPeca(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);
        $dbStub->method("lastInsertId")->willReturn("123");

        $peca = new PecaModel(
            id: 0,
            descricao: "Vela",
            valorUnitario: 22.75
        );

        $service = new PecaService($dbStub);
        $res = $service->criarPeca($peca);

        $this->assertInstanceOf(PecaModel::class, $res);
        $this->assertNotSame($peca, $res);
        $this->assertEquals(123, $res->getId());
        $this->assertEquals($peca->getDescricao(), $res->getDescricao());
        $this->assertEquals($peca->getValorUnitario(), $res->getValorUnitario());
    }

    public function testAtualizarPeca(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $peca = new PecaModel(
            id: 123,
            descricao: "Vela",
            valorUnitario: 22.75
        );

        $service = new PecaService($dbStub);
        $service->atualizarPeca($peca);
        $this->expectNotToPerformAssertions();
    }

}

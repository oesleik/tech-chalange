<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Veiculos\Model\VeiculoModel;
use App\Veiculos\Service\VeiculoService;
use PHPUnit\Framework\TestCase;

class VeiculoServiceTest extends TestCase {
    public function testListarVeiculos(): void {
		$mocks = [
            (object) [
                "id" => "123",
                "placa" => "ABC-1234",
                "marca" => "Volkswagen",
                "modelo" => "Gol",
            ],
            (object) [
                "id" => "456",
                "placa" => "AAA-4D56",
                "marca" => "Fiat",
                "modelo" => "Uno",
            ],
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("getIterator")->willReturn(new ArrayIterator($mocks));

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("query")->willReturn($stmtStub);

        $service = new VeiculoService($dbStub);
        $res = $service->listarVeiculos();
        $this->assertCount(2, $res);

        $this->assertInstanceOf(VeiculoModel::class, $res[0]);
        $this->assertEquals($mocks[0]->id, $res[0]->getId());
        $this->assertEquals($mocks[0]->placa, $res[0]->getPlaca());
        $this->assertEquals($mocks[0]->marca, $res[0]->getMarca());
        $this->assertEquals($mocks[0]->modelo, $res[0]->getModelo());

        $this->assertInstanceOf(VeiculoModel::class, $res[1]);
        $this->assertEquals($mocks[1]->id, $res[1]->getId());
        $this->assertEquals($mocks[1]->placa, $res[1]->getPlaca());
        $this->assertEquals($mocks[1]->marca, $res[1]->getMarca());
        $this->assertEquals($mocks[1]->modelo, $res[1]->getModelo());
	}

	public function testObterVeiculoPorId(): void {
        $mock = (object) [
            "id" => "123",
			"placa" => "ABC-1234",
			"marca" => "Volkswagen",
			"modelo" => "Gol",
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls($mock, false);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new VeiculoService($dbStub);
        $res = $service->obterVeiculoPorId(intval($mock->id));

        $this->assertInstanceOf(VeiculoModel::class, $res);
        $this->assertEquals($mock->id, $res->getId());
        $this->assertEquals($mock->placa, $res->getPlaca());
        $this->assertEquals($mock->marca, $res->getMarca());
        $this->assertEquals($mock->modelo, $res->getModelo());

        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(false);
        $res = $service->obterVeiculoPorId(789);
        $this->assertNull($res);
    }

	public function testObterVeiculoPorPlaca(): void {
        $mock = (object) [
            "id" => "123",
			"placa" => "ABC-1234",
			"marca" => "Volkswagen",
			"modelo" => "Gol",
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls($mock, false);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new VeiculoService($dbStub);
        $res = $service->obterVeiculoPorPlaca($mock->placa);

        $this->assertInstanceOf(VeiculoModel::class, $res);
        $this->assertEquals($mock->id, $res->getId());
        $this->assertEquals($mock->placa, $res->getPlaca());
        $this->assertEquals($mock->marca, $res->getMarca());
        $this->assertEquals($mock->modelo, $res->getModelo());

        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(false);
        $res = $service->obterVeiculoPorPlaca("AAA-4D56");
        $this->assertNull($res);
    }

	public function testCriarVeiculo(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);
        $dbStub->method("lastInsertId")->willReturn("123");

        $veiculo = new VeiculoModel(
            id: 0,
			placa: "ABC-1234",
			marca: "Volkswagen",
			modelo: "Gol",
        );

        $service = new VeiculoService($dbStub);
        $res = $service->criarVeiculo($veiculo);

        $this->assertInstanceOf(VeiculoModel::class, $res);
        $this->assertNotSame($veiculo, $res);
		$this->assertEquals(123, $res->getId());
        $this->assertEquals($veiculo->getPlaca(), $res->getPlaca());
        $this->assertEquals($veiculo->getMarca(), $res->getMarca());
        $this->assertEquals($veiculo->getModelo(), $res->getModelo());
    }

    public function testAtualizarVeiculo(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

         $veiculo = new VeiculoModel(
            id: 123,
			placa: "AAA-4D56",
			marca: "Fiat",
			modelo: "Uno",
        );

        $service = new VeiculoService($dbStub);
        $service->atualizarVeiculo($veiculo);
        $this->expectNotToPerformAssertions();
    }

}

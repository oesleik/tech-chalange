<?php

declare(strict_types=1);

use App\Clientes\Model\ClienteModel;
use App\Clientes\Service\ClienteService;
use App\Clientes\ValueObject\CnpjValue;
use App\Clientes\ValueObject\CpfValue;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use App\Core\AppDatabase;
use PHPUnit\Framework\TestCase;

class ClienteServiceTest extends TestCase {
    public function testListarClientes(): void {
        $mocks = [
            (object) [
                "id" => "123",
                "nome" => "Fulano de Tal",
                "cpf_cnpj" => "52998224725",
                "email" => "fulano@gmail.com",
                "telefone" => "54999999999",
            ],
            (object) [
                "id" => "456",
                "nome" => "Ciclano Ltda",
                "cpf_cnpj" => "AB345678000A91",
                "email" => "fulano@gmail.com",
                "telefone" => "54999999988",
            ],
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("getIterator")->willReturn(new ArrayIterator($mocks));

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("query")->willReturn($stmtStub);

        $service = new ClienteService($dbStub);
        $res = $service->listarClientes();
        $this->assertCount(2, $res);

        $this->assertInstanceOf(ClienteModel::class, $res[0]);
        $this->assertEquals($mocks[0]->id, $res[0]->getId());
        $this->assertInstanceOf(CpfValue::class, $res[0]->getCpfCnpj());
        $this->assertEquals($mocks[0]->cpf_cnpj, $res[0]->getCpfCnpj()->getValue());
        $this->assertEquals($mocks[0]->email, $res[0]->getEmail()->getValue());
        $this->assertEquals($mocks[0]->telefone, $res[0]->getTelefone()->getValue());

        $this->assertInstanceOf(ClienteModel::class, $res[1]);
        $this->assertEquals($mocks[1]->id, $res[1]->getId());
        $this->assertInstanceOf(CnpjValue::class, $res[1]->getCpfCnpj());
        $this->assertEquals($mocks[1]->cpf_cnpj, $res[1]->getCpfCnpj()->getValue());
        $this->assertEquals($mocks[1]->email, $res[1]->getEmail()->getValue());
        $this->assertEquals($mocks[1]->telefone, $res[1]->getTelefone()->getValue());
    }

    public function testListarClientesPorCpfCnpj(): void {
        $mock = (object) [
            "id" => "456",
            "nome" => "Ciclano Ltda",
            "cpf_cnpj" => "AB345678000A91",
            "email" => "fulano@gmail.com",
            "telefone" => "54999999988",
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls($mock, false);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new ClienteService($dbStub);
        $res = $service->listarClientes(new CnpjValue($mock->cpf_cnpj));
        $this->assertCount(1, $res);

        $this->assertInstanceOf(ClienteModel::class, $res[0]);
        $this->assertEquals($mock->id, $res[0]->getId());
        $this->assertInstanceOf(CnpjValue::class, $res[0]->getCpfCnpj());
        $this->assertEquals($mock->cpf_cnpj, $res[0]->getCpfCnpj()->getValue());
        $this->assertEquals($mock->email, $res[0]->getEmail()->getValue());
        $this->assertEquals($mock->telefone, $res[0]->getTelefone()->getValue());
    }

    public function testObterClientePorId(): void {
        $mock = (object) [
            "id" => "456",
            "nome" => "Ciclano Ltda",
            "cpf_cnpj" => "AB345678000A91",
            "email" => "fulano@gmail.com",
            "telefone" => "54999999988",
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls($mock, false);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new ClienteService($dbStub);
        $res = $service->obterClientePorId(intval($mock->id));

        $this->assertInstanceOf(ClienteModel::class, $res);
        $this->assertEquals($mock->id, $res->getId());
        $this->assertInstanceOf(CnpjValue::class, $res->getCpfCnpj());
        $this->assertEquals($mock->cpf_cnpj, $res->getCpfCnpj()->getValue());
        $this->assertEquals($mock->email, $res->getEmail()->getValue());
        $this->assertEquals($mock->telefone, $res->getTelefone()->getValue());

        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(false);
        $res = $service->obterClientePorId(789);
        $this->assertNull($res);
    }

    public function testCriarCliente(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);
        $dbStub->method("lastInsertId")->willReturn("123");

        $cliente = new ClienteModel(
            id: 0,
            nome: "Ciclano Ltda",
            cpfCnpj: new CnpjValue("AB345678000A91"),
            email: new EmailValue("fulano@gmail.com"),
            telefone: new TelefoneValue("54999999988"),
        );

        $service = new ClienteService($dbStub);
        $res = $service->criarCliente($cliente);

        $this->assertInstanceOf(ClienteModel::class, $res);
        $this->assertNotSame($cliente, $res);
        $this->assertEquals(123, $res->getId());
        $this->assertInstanceOf(CnpjValue::class, $res->getCpfCnpj());
        $this->assertEquals($cliente->getCpfCnpj()->getValue(), $res->getCpfCnpj()->getValue());
        $this->assertEquals($cliente->getEmail()->getValue(), $res->getEmail()->getValue());
        $this->assertEquals($cliente->getTelefone()->getValue(), $res->getTelefone()->getValue());
    }

    public function testAtualizarCliente(): void {
        $stmtStub = $this->createStub(PDOStatement::class);
        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $cliente = new ClienteModel(
            id: 123,
            nome: "Ciclano Ltda",
            cpfCnpj: new CnpjValue("AB345678000A91"),
            email: new EmailValue("fulano@gmail.com"),
            telefone: new TelefoneValue("54999999988"),
        );

        $service = new ClienteService($dbStub);
        $service->atualizarCliente($cliente);
        $this->expectNotToPerformAssertions();
    }

}

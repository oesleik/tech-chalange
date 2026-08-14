<?php

declare(strict_types=1);

use App\Clientes\Model\ClienteModel;
use App\Clientes\ValueObject\CnpjValue;
use App\Clientes\ValueObject\CpfValue;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use PHPUnit\Framework\TestCase;

class ClienteModelTest extends TestCase {
    public function testImmutable(): void {
        $base = new ClienteModel(
            id: 123,
            nome: "Fulano de Tal",
            cpfCnpj: new CpfValue("52998224725"),
            email: new EmailValue("fulano@gmail.com"),
            telefone: new TelefoneValue("54999999999"),
        );

        $this->assertEquals(123, $base->getId());
        $this->assertEquals("Fulano de Tal", $base->getNome());
        $this->assertEquals("52998224725", $base->getCpfCnpj()->getValue());
        $this->assertEquals("fulano@gmail.com", $base->getEmail()->getValue());
        $this->assertEquals("54999999999", $base->getTelefone()->getValue());

        $withId = $base->withId(456);
        $this->assertEquals(456, $withId->getId());

        $withNome = $base->withNome("Ciclano Ltda");
        $this->assertEquals("Ciclano Ltda", $withNome->getNome());

        $withCpfCnpj = $base->withCpfCnpj(new CnpjValue("AB345678000A91"));
        $this->assertEquals("AB345678000A91", $withCpfCnpj->getCpfCnpj()->getValue());

        $withEmail = $base->withEmail(new EmailValue("test@gmail.com"));
        $this->assertEquals("test@gmail.com", $withEmail->getEmail()->getValue());

        $withTelefone = $base->withTelefone(new TelefoneValue("54999999988"));
        $this->assertEquals("54999999988", $withTelefone->getTelefone()->getValue());

        $this->assertNotSame($base, $withId);
        $this->assertNotSame($base, $withNome);
        $this->assertNotSame($base, $withCpfCnpj);
        $this->assertNotSame($base, $withEmail);
        $this->assertNotSame($base, $withTelefone);
    }
}

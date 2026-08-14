<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EmailValueTest extends TestCase {
    public function testEmptyThrowsException(): void {
        $this->expectException(InvalidArgumentException::class);
        new EmailValue("");
    }

    public function testSemProviderThrowsException(): void {
        $this->expectException(InvalidArgumentException::class);
        new EmailValue("teste@");
    }

    public function testSemUsernameThrowsException(): void {
        $this->expectException(InvalidArgumentException::class);
        new EmailValue("@gmail");
    }

    public function testGetUnformattedValue(): void {
        $email = new EmailValue("fulano@gmail.com");
        $this->assertEquals("fulano@gmail.com", $email->getValue());
        $this->assertEquals("fulano@gmail.com", $email->__toString());
    }

    public function testGetMaskedValue(): void {
        $email = new EmailValue("fulano@gmail.com");
        $this->assertEquals("fu****@gmail.com", $email->getMaskedValue());
    }

    public function testGetMaskedValueEmailPequeno(): void {
        $email = new EmailValue("test@gmail.com");
        $this->assertEquals("****@gmail.com", $email->getMaskedValue());
    }

}

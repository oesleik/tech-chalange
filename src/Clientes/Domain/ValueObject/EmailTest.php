<?php

declare(strict_types=1);

namespace Tests\Clientes\Domain\ValueObject;

use App\Clientes\Domain\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase {
    public function testAceitaEmailValido(): void {
        $email = new Email('cliente@example.com');

        $this->assertSame('cliente@example.com', $email->getValue());
        $this->assertSame('cl*****@example.com', $email->getMaskedValue());
    }

    public function testRejeitaEmailInvalido(): void {
        $this->expectException(InvalidArgumentException::class);

        new Email('email-invalido');
    }
}

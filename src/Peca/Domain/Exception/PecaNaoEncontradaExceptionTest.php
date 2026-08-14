<?php

declare(strict_types=1);

namespace Tests\Peca\Domain\Exception;

use App\Peca\Domain\Exception\PecaNaoEncontradaException;
use PHPUnit\Framework\TestCase;

final class PecaNaoEncontradaExceptionTest extends TestCase {
    public function testComIdGeraMensagemComId(): void {
        $exception = PecaNaoEncontradaException::comId(42);

        $this->assertInstanceOf(PecaNaoEncontradaException::class, $exception);
        $this->assertSame('Peça com id 42 não encontrada.', $exception->getMessage());
    }
}

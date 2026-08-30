<?php

declare(strict_types=1);

namespace Tests\Servicos\Domain\Exception;

use App\Servicos\Domain\Exception\ServicoNaoEncontradoException;
use PHPUnit\Framework\TestCase;

final class ServicoNaoEncontradoExceptionTest extends TestCase {
    public function testComIdGeraMensagemComId(): void {
        $exception = ServicoNaoEncontradoException::comId(42);

        $this->assertInstanceOf(ServicoNaoEncontradoException::class, $exception);
        $this->assertSame('Serviço com id 42 não encontrado.', $exception->getMessage());
    }
}

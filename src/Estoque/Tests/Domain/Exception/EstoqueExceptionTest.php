<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Domain\Exception;

use App\Estoque\Domain\Exception\EstoqueInsuficienteException;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;
use PHPUnit\Framework\TestCase;

final class EstoqueExceptionTest extends TestCase {
    public function testPecaNaoEncontradaMontaMensagemComId(): void {
        $exception = PecaNaoEncontradaException::comId(15);

        $this->assertSame('Peça com id 15 não encontrada.', $exception->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testEstoqueInsuficienteMontaMensagemComValores(): void {
        $exception = EstoqueInsuficienteException::para(15, 2, 7);

        $this->assertSame(
            'Estoque insuficiente para a peça 15. Disponível: 2, solicitado: 7.',
            $exception->getMessage(),
        );
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}

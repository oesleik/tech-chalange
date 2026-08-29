<?php

declare(strict_types=1);

namespace Tests\Veiculos\Domain\Exception;

use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use PHPUnit\Framework\TestCase;

final class VeiculoExceptionsTest extends TestCase {
    public function testJaCadastradoComPlacaMontaMensagem(): void {
        $excecao = VeiculoJaCadastradoException::comPlaca('ABC1D23');

        $this->assertSame('Veículo com placa ABC1D23 já cadastrado.', $excecao->getMessage());
    }

    public function testNaoEncontradoComIdMontaMensagem(): void {
        $excecao = VeiculoNaoEncontradoException::comId(10);

        $this->assertSame('Veículo com id 10 não encontrado.', $excecao->getMessage());
    }

    public function testNaoEncontradoComPlacaMontaMensagem(): void {
        $excecao = VeiculoNaoEncontradoException::comPlaca('ABC1D23');

        $this->assertSame('Veículo com placa ABC1D23 não encontrado.', $excecao->getMessage());
    }
}

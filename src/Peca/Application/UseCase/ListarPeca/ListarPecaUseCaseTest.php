<?php

declare(strict_types=1);

namespace Tests\Peca\Application\UseCase;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Application\UseCase\ListarPeca\ListarPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;
use PHPUnit\Framework\TestCase;

final class ListarPecaUseCaseTest extends TestCase {
    public function testRetornaListaDePecas(): void {
        $pecas = [
            Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90)),
            Peca::reconstituir(2, 'Correia dentada', new ValorUnitario(120.0)),
        ];

        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('listar')->willReturn($pecas);

        $resultado = (new ListarPecaUseCase($gateway))->executar();

        $this->assertCount(2, $resultado);
        $this->assertSame('Filtro de óleo', $resultado[0]->descricao());
        $this->assertSame('Correia dentada', $resultado[1]->descricao());
    }

    public function testRetornaListaVaziaQuandoNaoHaPecas(): void {
        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('listar')->willReturn([]);

        $resultado = (new ListarPecaUseCase($gateway))->executar();

        $this->assertSame([], $resultado);
    }
}
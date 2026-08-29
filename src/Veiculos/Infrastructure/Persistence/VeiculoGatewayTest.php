<?php

declare(strict_types=1);

namespace Tests\Veiculos\Infrastructure\Persistence;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Veiculos\Application\Gateway\FiltroListagemVeiculo;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Infrastructure\Persistence\VeiculoGateway;
use PHPUnit\Framework\TestCase;

final class VeiculoGatewayTest extends TestCase {
    public function testBuscaPorIdMapeiaRegistroParaEntidade(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('buscarPorParametros')
            ->with('veiculos', null, ['id' => 1])
            ->willReturn([$this->linha()]);

        $veiculo = new VeiculoGateway($connection)->buscarPorId(1);

        $this->assertInstanceOf(Veiculo::class, $veiculo);
        $this->assertSame('Fiat', $veiculo->marca());
    }

    public function testRetornaNullQuandoNaoEncontraPorId(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->method('buscarPorParametros')->willReturn([]);

        $veiculo = new VeiculoGateway($connection)->buscarPorId(999);

        $this->assertNull($veiculo);
    }

    public function testBuscaPorPlacaMapeiaRegistroParaEntidade(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('buscarPorParametros')
            ->with('veiculos', null, ['placa' => 'ABC1D23'])
            ->willReturn([$this->linha()]);

        $veiculo = new VeiculoGateway($connection)->buscarPorPlaca(new Placa('ABC1D23'));

        $this->assertInstanceOf(Veiculo::class, $veiculo);
        $this->assertSame('ABC1D23', $veiculo->placa()->getValue());
    }

    public function testRetornaNullQuandoNaoEncontraPorPlaca(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->method('buscarPorParametros')->willReturn([]);

        $veiculo = new VeiculoGateway($connection)->buscarPorPlaca(new Placa('ABC1D23'));

        $this->assertNull($veiculo);
    }

    public function testInsereVeiculoEAdicionaIdGerado(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())->method('inserir')->with(
            'veiculos',
            [
                'placa' => 'ABC1D23',
                'marca' => 'Fiat',
                'modelo' => 'Uno',
            ],
        )->willReturn(5);

        $resultado = new VeiculoGateway($connection)->inserir($this->veiculo());

        $this->assertSame(5, $resultado->id());
    }

    public function testAtualizaVeiculoERetornaEntidade(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())->method('atualizar')->with(
            'veiculos',
            [
                'placa' => 'ABC1D23',
                'marca' => 'Fiat',
                'modelo' => 'Uno',
            ],
            ['id' => 1],
        );

        $veiculo = $this->veiculo()->comId(1);
        $resultado = new VeiculoGateway($connection)->atualizar($veiculo);

        $this->assertSame($veiculo, $resultado);
    }

    public function testListaVeiculosComFiltroCompleto(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('buscarComFiltro')
            ->with(
                tabela: 'veiculos',
                condicoesExatas: ['placa' => 'ABC1D23'],
                condicoesParciais: ['marca' => 'Fiat', 'modelo' => 'Uno'],
                limite: 10,
                offset: 0,
            )
            ->willReturn([$this->linha()]);

        $filtro = new FiltroListagemVeiculo(
            placa: new Placa('ABC1D23'),
            marca: 'Fiat',
            modelo: 'Uno',
            pagina: 1,
            porPagina: 10,
        );

        $resultado = new VeiculoGateway($connection)->listar($filtro);

        $this->assertCount(1, $resultado);
        $this->assertInstanceOf(Veiculo::class, $resultado[0]);
    }

    public function testListaVeiculosSemFiltrosOpcionais(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('buscarComFiltro')
            ->with(
                tabela: 'veiculos',
                condicoesExatas: [],
                condicoesParciais: [],
                limite: 10,
                offset: 10,
            )
            ->willReturn([]);

        $filtro = new FiltroListagemVeiculo(
            placa: null,
            marca: null,
            modelo: null,
            pagina: 2,
            porPagina: 10,
        );

        $resultado = new VeiculoGateway($connection)->listar($filtro);

        $this->assertSame([], $resultado);
    }

    public function testContaVeiculosComFiltro(): void {
        $connection = $this->createMock(DbConnectionInterface::class);
        $connection->expects($this->once())
            ->method('contarComFiltro')
            ->with(
                tabela: 'veiculos',
                condicoesExatas: ['placa' => 'ABC1D23'],
                condicoesParciais: ['marca' => 'Fiat', 'modelo' => 'Uno'],
            )
            ->willReturn(3);

        $filtro = new FiltroListagemVeiculo(
            placa: new Placa('ABC1D23'),
            marca: 'Fiat',
            modelo: 'Uno',
            pagina: 1,
            porPagina: 10,
        );

        $resultado = new VeiculoGateway($connection)->contar($filtro);

        $this->assertSame(3, $resultado);
    }

    /** @return array<string, mixed> */
    private function linha(): array {
        return [
            'id' => 1,
            'placa' => 'ABC1D23',
            'marca' => 'Fiat',
            'modelo' => 'Uno',
        ];
    }

    private function veiculo(): Veiculo {
        return new Veiculo(
            id: 0,
            placa: new Placa('ABC1D23'),
            marca: 'Fiat',
            modelo: 'Uno',
        );
    }
}

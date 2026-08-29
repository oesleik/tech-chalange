<?php

declare(strict_types=1);

namespace Tests\Veiculos\Presentation\Http\DTO;

use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoInputDTO;
use App\Veiculos\Presentation\Http\DTO\ListarVeiculoMapper;
use PHPUnit\Framework\TestCase;

final class ListarVeiculoMapperTest extends TestCase {
    public function testUsaValoresPadraoQuandoQueryVazia(): void {
        $dto = ListarVeiculoMapper::fromQueryParams([]);

        $this->assertNull($dto->placa);
        $this->assertNull($dto->marca);
        $this->assertNull($dto->modelo);
        $this->assertSame(ListarVeiculoInputDTO::PAGINA_PADRAO, $dto->pagina);
        $this->assertSame(ListarVeiculoInputDTO::POR_PAGINA_PADRAO, $dto->porPagina);
    }

    public function testParseiaTodosOsParametros(): void {
        $dto = ListarVeiculoMapper::fromQueryParams([
            'placa' => 'ABC1D23',
            'marca' => ' Toyota ',
            'modelo' => ' Corolla ',
            'pagina' => '2',
            'porPagina' => '50',
        ]);

        $this->assertSame('ABC1D23', $dto->placa->getValue());
        $this->assertSame('Toyota', $dto->marca);
        $this->assertSame('Corolla', $dto->modelo);
        $this->assertSame(2, $dto->pagina);
        $this->assertSame(50, $dto->porPagina);
    }

    public function testNormalizaStringVaziaComoNull(): void {
        $dto = ListarVeiculoMapper::fromQueryParams(['marca' => '   ', 'modelo' => '']);

        $this->assertNull($dto->marca);
        $this->assertNull($dto->modelo);
    }

    public function testPaginaMinimaEUm(): void {
        $dto = ListarVeiculoMapper::fromQueryParams(['pagina' => '-5']);

        $this->assertSame(1, $dto->pagina);
    }

    public function testPorPaginaRespeitaMaximo(): void {
        $dto = ListarVeiculoMapper::fromQueryParams(['porPagina' => '9999']);

        $this->assertSame(ListarVeiculoInputDTO::POR_PAGINA_MAXIMO, $dto->porPagina);
    }

    public function testPorPaginaRespeitaMinimo(): void {
        $dto = ListarVeiculoMapper::fromQueryParams(['porPagina' => '0']);

        $this->assertSame(1, $dto->porPagina);
    }
}

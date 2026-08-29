<?php

declare(strict_types=1);

namespace Tests\Veiculos\Presentation\Http\DTO;

use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoOutputDTO;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Presentation\Http\DTO\ListagemVeiculosResponseDTO;
use App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO;
use PHPUnit\Framework\TestCase;

final class VeiculoResponseDTOTest extends TestCase {
    public function testFromEntityMapeiaCamposEFormataPlaca(): void {
        $veiculo = new Veiculo(
            id: 10,
            placa: new Placa('ABC1D23'),
            marca: 'Toyota',
            modelo: 'Corolla',
        );

        $dto = VeiculoResponseDTO::fromEntity($veiculo);

        $this->assertSame(10, $dto->id);
        $this->assertSame('ABC-1D23', $dto->placa);
        $this->assertSame('Toyota', $dto->marca);
        $this->assertSame('Corolla', $dto->modelo);
    }

    public function testListagemFromOutputDTOMapeiaVeiculosEPaginacao(): void {
        $veiculo = new Veiculo(id: 1, placa: new Placa('ABC1D23'), marca: 'Toyota', modelo: 'Corolla');
        $output = new ListarVeiculoOutputDTO(
            veiculos: [$veiculo],
            total: 1,
            pagina: 1,
            porPagina: 20,
        );

        $dto = ListagemVeiculosResponseDTO::fromOutputDTO($output);

        $this->assertCount(1, $dto->dados);
        $this->assertInstanceOf(VeiculoResponseDTO::class, $dto->dados[0]);
        $this->assertSame(1, $dto->paginacao->pagina);
        $this->assertSame(1, $dto->paginacao->total);
        $this->assertSame(1, $dto->paginacao->totalPaginas);
    }

    public function testListagemVaziaRetornaArrayVazio(): void {
        $output = new ListarVeiculoOutputDTO(veiculos: [], total: 0, pagina: 1, porPagina: 20);

        $dto = ListagemVeiculosResponseDTO::fromOutputDTO($output);

        $this->assertSame([], $dto->dados);
        $this->assertSame(0, $dto->paginacao->total);
    }
}

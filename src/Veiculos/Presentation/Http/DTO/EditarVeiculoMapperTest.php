<?php

declare(strict_types=1);

namespace Tests\Veiculos\Presentation\Http\DTO;

use App\Veiculos\Presentation\Http\DTO\EditarVeiculoMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EditarVeiculoMapperTest extends TestCase {
    public function testParseiaTodosOsCamposInformados(): void {
        $dto = EditarVeiculoMapper::parse([
            'placa' => ' abc1d23 ',
            'marca' => ' Toyota ',
            'modelo' => ' Corolla ',
        ]);

        $this->assertSame('abc1d23', $dto->placa);
        $this->assertSame('Toyota', $dto->marca);
        $this->assertSame('Corolla', $dto->modelo);
    }

    public function testCamposAusentesResultamEmNull(): void {
        $dto = EditarVeiculoMapper::parse([]);

        $this->assertNull($dto->placa);
        $this->assertNull($dto->marca);
        $this->assertNull($dto->modelo);
    }

    public function testCamposComValorNullResultamEmNull(): void {
        $dto = EditarVeiculoMapper::parse(['placa' => null, 'marca' => null, 'modelo' => null]);

        $this->assertNull($dto->placa);
        $this->assertNull($dto->marca);
        $this->assertNull($dto->modelo);
    }

    public function testCamposComStringVaziaResultamEmNull(): void {
        $dto = EditarVeiculoMapper::parse(['placa' => '   ', 'marca' => '', 'modelo' => '  ']);

        $this->assertNull($dto->placa);
        $this->assertNull($dto->marca);
        $this->assertNull($dto->modelo);
    }

    public function testLancaExcecaoQuandoCampoNaoEhString(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Campo 'marca' deve ser uma string.");

        EditarVeiculoMapper::parse(['marca' => 123]);
    }
}

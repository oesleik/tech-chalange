<?php

declare(strict_types=1);

namespace Tests\Clientes\Presentation\Http\DTO;

use App\Clientes\Presentation\Http\DTO\ListarClientesMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ListarClientesMapperTest extends TestCase {
    public function testSemFiltroCriaInputVazio(): void {
        $input = ListarClientesMapper::fromQueryParams([]);

        $this->assertNull($input->cpfCnpj);
    }

    public function testConverteFiltroCpfCnpj(): void {
        $input = ListarClientesMapper::fromQueryParams(['cpf_cnpj' => ' 52998224725 ']);

        $this->assertSame('52998224725', $input->cpfCnpj);
    }

    public function testLancaExcecaoQuandoFiltroNaoEString(): void {
        $this->expectException(InvalidArgumentException::class);

        ListarClientesMapper::fromQueryParams(['cpf_cnpj' => 123]);
    }
}

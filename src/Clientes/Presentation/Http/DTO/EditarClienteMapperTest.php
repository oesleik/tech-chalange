<?php

declare(strict_types=1);

namespace Tests\Clientes\Presentation\Http\DTO;

use App\Clientes\Presentation\Http\DTO\EditarClienteMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EditarClienteMapperTest extends TestCase {
    public function testParseAceitaCamposParciais(): void {
        $input = EditarClienteMapper::parse([
            'nome' => ' Novo nome ',
            'email' => null,
        ]);

        $this->assertSame('Novo nome', $input->nome);
        $this->assertNull($input->cpfCnpj);
        $this->assertNull($input->email);
        $this->assertNull($input->telefone);
    }

    public function testParseSemCamposRetornaInputVazio(): void {
        $input = EditarClienteMapper::parse([]);

        $this->assertNull($input->nome);
        $this->assertNull($input->cpfCnpj);
    }

    public function testLancaExcecaoQuandoCampoNaoEString(): void {
        $this->expectException(InvalidArgumentException::class);

        EditarClienteMapper::parse(['nome' => 123]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Clientes\Presentation\Http\DTO;

use App\Clientes\Presentation\Http\DTO\CriarClienteMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CriarClienteMapperTest extends TestCase {
    public function testParseComDadosValidos(): void {
        $input = CriarClienteMapper::parse([
            'nome' => ' Fulano de Tal ',
            'cpf_cnpj' => '529.982.247-25',
            'email' => 'fulano@example.com',
            'telefone' => '5412345678',
        ]);

        $this->assertSame('Fulano de Tal', $input->nome);
        $this->assertSame('529.982.247-25', $input->cpfCnpj);
        $this->assertSame('fulano@example.com', $input->email);
    }

    public function testLancaExcecaoQuandoCampoObrigatorioNaoExiste(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome é obrigatório.');

        CriarClienteMapper::parse([]);
    }

    public function testLancaExcecaoQuandoTelefonePossuiCaracterInvalido(): void {
        $this->expectException(InvalidArgumentException::class);

        CriarClienteMapper::parse([
            'nome' => 'Fulano',
            'cpf_cnpj' => '52998224725',
            'email' => 'fulano@example.com',
            'telefone' => 'abc123',
        ]);
    }
}

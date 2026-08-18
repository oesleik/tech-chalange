<?php

declare(strict_types=1);

namespace Tests\Clientes\Domain\Entity;

use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ClienteTest extends TestCase {
    public function testCriaClienteComDadosValidos(): void {
        $cliente = Cliente::criar(
            'Fulano de Tal',
            new Cpf('529.982.247-25'),
            new Email('fulano@example.com'),
            new Telefone('5412345678'),
        );

        $this->assertNull($cliente->id());
        $this->assertSame('Fulano de Tal', $cliente->nome());
        $this->assertSame('52998224725', $cliente->cpfCnpj()->getValue());
    }

    public function testNomeVazioNaoEPermitido(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nome é obrigatório.');

        Cliente::criar(
            '   ',
            new Cpf('529.982.247-25'),
            new Email('fulano@example.com'),
            new Telefone('5412345678'),
        );
    }

    public function testMetodosComRetornamNovaEntidadeSemAlterarOriginal(): void {
        $cliente = Cliente::reconstituir(
            1,
            'Fulano',
            new Cpf('529.982.247-25'),
            new Email('fulano@example.com'),
            new Telefone('5412345678'),
        );

        $alterado = $cliente->comNome('Beltrano')->comId(2);

        $this->assertSame(1, $cliente->id());
        $this->assertSame('Fulano', $cliente->nome());
        $this->assertSame(2, $alterado->id());
        $this->assertSame('Beltrano', $alterado->nome());
    }
}

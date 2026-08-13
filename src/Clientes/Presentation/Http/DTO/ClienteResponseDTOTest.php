<?php

declare(strict_types=1);

namespace Tests\Clientes\Presentation\Http\DTO;

use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\Clientes\Presentation\Http\DTO\ClienteResponseDTO;
use PHPUnit\Framework\TestCase;

final class ClienteResponseDTOTest extends TestCase {
    public function testConverteEntidadeSemMascarar(): void {
        $dto = ClienteResponseDTO::fromEntity($this->cliente());

        $this->assertSame(1, $dto->id);
        $this->assertSame('529.982.247-25', $dto->cpf_cnpj);
        $this->assertSame('cliente@example.com', $dto->email);
    }

    public function testConverteEntidadeMascarandoDadosSensíveis(): void {
        $dto = ClienteResponseDTO::fromEntity($this->cliente(), true);

        $this->assertSame('52*.***.***-25', $dto->cpf_cnpj);
        $this->assertSame('cl*****@example.com', $dto->email);
        $this->assertSame('********78', $dto->telefone);
    }

    private function cliente(): Cliente {
        return Cliente::reconstituir(
            1,
            'Cliente',
            new Cpf('52998224725'),
            new Email('cliente@example.com'),
            new Telefone('5412345678'),
        );
    }
}

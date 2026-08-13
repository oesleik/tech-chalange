<?php

declare(strict_types=1);

namespace Tests\Clientes\Domain\ValueObject;

use App\Clientes\Domain\ValueObject\Cnpj;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\CpfOrCnpjValueFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CpfOrCnpjValueFactoryTest extends TestCase {
    public function testCriaCpfQuandoValorPossuiOnzeDigitosValidos(): void {
        $this->assertInstanceOf(Cpf::class, CpfOrCnpjValueFactory::make('52998224725'));
    }

    public function testCriaCnpjQuandoValorPossuiQuatorzeDigitosValidos(): void {
        $this->assertInstanceOf(Cnpj::class, CpfOrCnpjValueFactory::make('11222333000181'));
    }

    public function testRejeitaCpfOuCnpjInvalido(): void {
        $this->expectException(InvalidArgumentException::class);

        CpfOrCnpjValueFactory::make('invalido');
    }
}

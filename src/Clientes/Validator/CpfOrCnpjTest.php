<?php

declare(strict_types=1);

use App\Clientes\Validator\CpfOrCnpj;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;

require_once __DIR__ . "/CpfValidatorTest.php";
require_once __DIR__ . "/CnpjValidatorTest.php";
require_once __DIR__ . "/SymfonyIdentityTranslatorTestStub.php";

class CpfOrCnpjTest extends CompoundConstraintTestCase {
    protected function createCompound(): Compound {
        return new CpfOrCnpj();
    }

    public function testNullIsValid(): void {
        $this->validateValue(null);
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void {
        $this->validateValue('');
        $this->assertNoViolation();
    }

    #[DataProviderExternal(CpfValidatorTest::class, 'provideValidCpfs')]
    public function testValidCpfIsValid(string $cpf): void {
        $this->validateValue($cpf);
        $this->assertNoViolation();
    }

    #[DataProviderExternal(CnpjValidatorTest::class, 'provideValidCnpjs')]
    public function testValidCnpjIsValid(string $cnpj): void {
        $this->validateValue($cnpj);
        $this->assertNoViolation();
    }

    #[DataProvider('provideInvalidValues')]
    public function testInvalidValueIsInvalid(string $value): void {
        $this->validateValue($value);
        $this->assertViolationsCount(1);
    }

    public static function provideInvalidValues(): \Generator {
        yield ['04252011000111'];
        yield ['AB345678000A92'];
        yield ['123'];
    }

}

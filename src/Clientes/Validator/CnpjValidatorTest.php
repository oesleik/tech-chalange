<?php

declare(strict_types=1);

use App\Clientes\Validator\Cnpj;
use App\Clientes\Validator\CnpjValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class CnpjValidatorTest extends ConstraintValidatorTestCase {

    protected function createValidator(): ConstraintValidatorInterface {
        return new CnpjValidator();
    }

    public function testNullIsValid(): void {
        $this->validate(null, new Cnpj());
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void {
        $this->validate('', new Cnpj());
        $this->assertNoViolation();
    }

    #[DataProvider('provideValidCnpjs')]
    public function testValidCnpjIsValid(string $cnpj): void {
        $this->validate($cnpj, new Cnpj());
        $this->assertNoViolation();
    }

    public static function provideValidCnpjs(): \Generator {
		yield ['04.252.011/0001-10'];
        yield ['AB345678000A91'];
    }

    #[DataProvider('provideInvalidCnpjs')]
    public function testInvalidCnpjIsInvalid(string $cnpj): void {
		$constraint = new Cnpj();
        $this->validate($cnpj, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ string }}', $cnpj)
            ->assertRaised();
    }

    public static function provideInvalidCnpjs(): \Generator {
        yield ['04252011000111'];
        yield ['AB345678000A92'];
        yield ['123'];
    }

    public function testNonStringValueThrowsUnexpectedValueException(): void {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(123, new Cnpj());
    }

    public function testUnexpectedConstraintTypeThrowsUnexpectedTypeException(): void {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('04252011000110', new NotNull());
    }

}

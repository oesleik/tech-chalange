<?php

declare(strict_types=1);

use App\Clientes\Validator\Cpf;
use App\Clientes\Validator\CpfValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class CpfValidatorTest extends ConstraintValidatorTestCase {
    protected function createValidator(): ConstraintValidatorInterface {
        return new CpfValidator();
    }

    public function testNullIsValid(): void {
        $this->validate(null, new Cpf());
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void {
        $this->validate('', new Cpf());
        $this->assertNoViolation();
    }

    #[DataProvider('provideValidCpfs')]
    public function testValidCpfIsValid(string $cpf): void {
        $this->validate($cpf, new Cpf());
        $this->assertNoViolation();
    }

    public static function provideValidCpfs(): \Generator {
        yield ['52998224725'];
        yield ['529.982.247-25'];
    }

    #[DataProvider('provideInvalidCpfs')]
    public function testInvalidCpfIsInvalid(string $cpf): void {
        $constraint = new Cpf();
        $this->validate($cpf, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ string }}', $cpf)
            ->assertRaised();
    }

    public static function provideInvalidCpfs(): \Generator {
        yield ['12345678900'];
        yield ['52998224724'];
        yield ['529.982.247-24'];
        yield ['123'];
    }

    public function testNonStringValueThrowsUnexpectedValueException(): void {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(123, new Cpf());
    }

    public function testUnexpectedConstraintTypeThrowsUnexpectedTypeException(): void {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('52998224725', new NotNull());
    }
}

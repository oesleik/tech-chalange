<?php

declare(strict_types=1);

use App\Veiculos\Validator\Placa;
use App\Veiculos\Validator\PlacaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PlacaValidatorTest extends ConstraintValidatorTestCase {
    protected function createValidator(): ConstraintValidatorInterface {
        return new PlacaValidator();
    }

    public function testNullIsValid(): void {
        $this->validate(null, new Placa());
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void {
        $this->validate('', new Placa());
        $this->assertNoViolation();
    }

    #[DataProvider('provideValidPlacas')]
    public function testValidPlacaIsValid(string $placa): void {
        $this->validate($placa, new Placa());
        $this->assertNoViolation();
    }

    public static function provideValidPlacas(): \Generator {
        yield ['ABC1234'];
        yield ['ABC1D34'];
        yield ['ABC-1234'];
        yield ['ABC 1D34'];
    }

    #[DataProvider('provideInvalidPlacas')]
    public function testInvalidPlacaIsInvalid(string $placa): void {
        $constraint = new Placa();
        $this->validate($placa, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ string }}', $placa)
            ->assertRaised();
    }

    public static function provideInvalidPlacas(): \Generator {
        yield ['123ABCD'];
        yield ['ABCDEFG'];
        yield ['ABC12345'];
        yield ['123'];
    }

    public function testNonStringValueThrowsUnexpectedValueException(): void {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(123, new Placa());
    }

    public function testUnexpectedConstraintTypeThrowsUnexpectedTypeException(): void {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate("ABC1234", new NotNull());
    }
}

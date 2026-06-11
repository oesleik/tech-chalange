<?php

declare(strict_types=1);

namespace App\Core\Contract;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

#[OA\Schema]
readonly class ValidationErrorResponse {
    public function __construct(
        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ValidationError')
        )]
        public array $errors,
    ) {}

    public static function from(ConstraintViolationListInterface $violations): self {
        return new self(array_map(fn(ConstraintViolationInterface $v) =>  new ValidationError(
            field: $v->getPropertyPath(),
            message: $v->getMessage()
        ), iterator_to_array($violations)));
    }
}

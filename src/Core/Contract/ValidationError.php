<?php

declare(strict_types=1);

namespace App\Core\Contract;

use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ValidationError {
    public function __construct(
        public string $field,
        public string $message,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Core\Contract;

use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ApiLinkSchema {
    public function __construct(
        #[OA\Property(example: "self")]
        public string $rel,
        #[OA\Property(example: "http://localhost/resource")]
        public string $href,
        #[OA\Property(example: "GET")]
        public string $method,
    ) {}
}

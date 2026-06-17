<?php

declare(strict_types=1);

namespace App\Servicos\Contract;

use App\Core\Contract\AbstractContract;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ListarServicosResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/ServicoResponse'))]
        public array $servicos
    ) {}
}

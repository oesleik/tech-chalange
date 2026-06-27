<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ListarOrdensServicoResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/OrdemServicoResumidaResponse'))]
        public array $ordens_servico
    ) {}
}

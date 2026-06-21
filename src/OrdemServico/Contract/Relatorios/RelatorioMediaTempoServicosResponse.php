<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract\Relatorios;

use App\Core\Contract\AbstractContract;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class RelatorioMediaTempoServicosResponse extends AbstractContract {
    /** @param ServicoRelatorioMediaTempoServicosResponse[] $servicos */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: '#/components/schemas/ServicoRelatorioMediaTempoServicosResponse'))]
        public array $servicos
    ) {}

}

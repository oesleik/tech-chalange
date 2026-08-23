<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\Gateway;

use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;

interface ItensOrdemServicoGatewayInterface {
    /** @return PecaOrdemServico[] */
    public function buscarPecasPorOrdemServico(int $idOrdemServico): array;

    /** @return ServicoOrdemServico[] */
    public function buscarServicosPorOrdemServico(int $idOrdemServico): array;

    /** @param PecaOrdemServico[] $pecas */
    public function adicionarPecas(OrdemServico $ordemServico, array $pecas): void;

    /** @param PecaOrdemServico[] $pecas */
    public function substituirPecas(OrdemServico $ordemServico, array $pecas): void;

    /** @param ServicoOrdemServico[] $servicos */
    public function adicionarServicos(OrdemServico $ordemServico, array $servicos): void;

    /** @param ServicoOrdemServico[] $servicos */
    public function substituirServicos(OrdemServico $ordemServico, array $servicos): void;
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\Gateway;

use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\ValueObject\FiltroOrdemServico;

interface OrdemServicoGatewayInterface {
    public function buscarPorId(int $id): ?OrdemServico;

    /** @return OrdemServico[] */
    public function listar(FiltroOrdemServico $filtro): array;

    public function inserir(OrdemServico $ordemServico): OrdemServico;

    public function atualizarSituacao(OrdemServico $ordemServico): OrdemServico;

    public function atualizarValorTotal(int $id, float $valorTotal): void;

    public function obterProximaNaFila(): ?OrdemServico;
}

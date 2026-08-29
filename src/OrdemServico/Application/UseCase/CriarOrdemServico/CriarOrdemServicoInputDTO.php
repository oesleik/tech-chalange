<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\CriarOrdemServico;

final class CriarOrdemServicoInputDTO {
    /** @param array<array{id_peca: int, quantidade: int}> $pecas */
    /** @param array<array{id_servico: int, quantidade: int}> $servicos */
    public function __construct(
        public readonly int $idCliente,
        public readonly int $idVeiculo,
        public readonly array $pecas = [],
        public readonly array $servicos = [],
    ) {}
}

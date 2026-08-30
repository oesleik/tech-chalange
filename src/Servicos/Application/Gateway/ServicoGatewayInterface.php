<?php

declare(strict_types=1);

namespace App\Servicos\Application\Gateway;

use App\Servicos\Domain\Entity\Servico;

interface ServicoGatewayInterface {
    public function buscarPorId(int $id): ?Servico;
    public function inserir(Servico $servico): Servico;
    public function atualizar(Servico $servico): Servico;

    /** @return Servico[] */
    public function listar(): array;
}

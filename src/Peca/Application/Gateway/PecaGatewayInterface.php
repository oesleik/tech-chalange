<?php

declare(strict_types=1);

namespace App\Peca\Application\Gateway;

use App\Peca\Domain\Entity\Peca;

interface PecaGatewayInterface {
    public function buscarPorId(int $id): ?Peca;
    public function inserir(Peca $peca): Peca;
    public function atualizar(Peca $peca): Peca;

    /** @return Peca[] */
    public function listar(): array;
}
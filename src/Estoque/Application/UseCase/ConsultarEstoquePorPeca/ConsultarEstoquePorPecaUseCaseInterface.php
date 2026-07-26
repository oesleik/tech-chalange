<?php

declare(strict_types=1);

namespace App\Estoque\Application\UseCase\ConsultarEstoquePorPeca;

interface ConsultarEstoquePorPecaUseCaseInterface {
    public function executar(int $pecaId): ConsultarEstoquePorPecaOutputDTO;
}

<?php

declare(strict_types=1);

namespace App\Estoque\Application\UseCase\ConsultarEstoquePorPeca;

use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;

final class ConsultarEstoquePorPecaUseCase implements ConsultarEstoquePorPecaUseCaseInterface {
    public function __construct(private readonly EstoqueGatewayInterface $gateway) {}

    public function executar(int $pecaId): ConsultarEstoquePorPecaOutputDTO {
        if (!$this->gateway->pecaExiste($pecaId)) {
            throw PecaNaoEncontradaException::comId($pecaId);
        }

        return new ConsultarEstoquePorPecaOutputDTO(
            pecaId: $pecaId,
            estoqueAtual: $this->gateway->calcularEstoqueAtual($pecaId),
        );
    }
}

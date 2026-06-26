<?php

declare(strict_types=1);

namespace App\OrdemServico\Model;

use App\OrdemServico\Model\SituacaoOrdemServicoEnum;

readonly class FiltroOrdemServico {
    public function __construct(
        private ?SituacaoOrdemServicoEnum $situacao = null,
        private ?int $idCliente = null,
        private ?int $idVeiculo = null,
        private ?int $idOrdem = null,
    ) {}

    public function getSituacao(): ?SituacaoOrdemServicoEnum {
        return $this->situacao;
    }

    public function getIdCliente(): ?int {
        return $this->idCliente;
    }

    public function getIdVeiculo(): ?int {
        return $this->idVeiculo;
    }

    public function getIdOrdem(): ?int {
        return $this->idOrdem;
    }

    public function temFiltroAtivo(): bool {
        return $this->situacao !== null || $this->idCliente !== null || $this->idVeiculo !== null || $this->idOrdem !== null;
    }

    public static function fromArray(array $dados): self {
        $situacao = null;
        if (isset($dados['situacao'])) {
            try {
                $situacao = SituacaoOrdemServicoEnum::from($dados['situacao']);
            } catch (\ValueError) {
                // Ignorar situação inválida
            }
        }

        return new self(
            situacao: $situacao,
            idCliente: isset($dados['id_cliente']) ? (int) $dados['id_cliente'] : null,
            idVeiculo: isset($dados['id_veiculo']) ? (int) $dados['id_veiculo'] : null,
            idOrdem: isset($dados['id']) ? (int) $dados['id'] : null,
        );
    }
}

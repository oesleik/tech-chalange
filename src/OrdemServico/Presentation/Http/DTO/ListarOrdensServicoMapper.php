<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Application\UseCase\ListarOrdensServico\ListarOrdensServicoInputDTO;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use InvalidArgumentException;

final class ListarOrdensServicoMapper {
    public static function fromQueryParams(array $params): ListarOrdensServicoInputDTO {
        $situacao   = null;
        $idCliente  = null;
        $idVeiculo  = null;

        if (!empty($params['situacao'])) {
            $situacao = SituacaoOrdemServicoEnum::tryFrom($params['situacao'])
                ?? throw new InvalidArgumentException("Situação inválida: {$params['situacao']}");
        }

        if (!empty($params['id_cliente'])) {
            $idCliente = (int) $params['id_cliente'];
            if ($idCliente <= 0) {
                throw new InvalidArgumentException('id_cliente deve ser um inteiro positivo.');
            }
        }

        if (!empty($params['id_veiculo'])) {
            $idVeiculo = (int) $params['id_veiculo'];
            if ($idVeiculo <= 0) {
                throw new InvalidArgumentException('id_veiculo deve ser um inteiro positivo.');
            }
        }

        return new ListarOrdensServicoInputDTO(
            situacao: $situacao,
            idCliente: $idCliente,
            idVeiculo: $idVeiculo,
        );
    }
}

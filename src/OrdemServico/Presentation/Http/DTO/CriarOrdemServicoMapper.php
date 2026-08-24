<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoInputDTO;
use InvalidArgumentException;

final class CriarOrdemServicoMapper {
    public static function parse(array $data): CriarOrdemServicoInputDTO {
        if (empty($data['id_cliente']) || !is_int($data['id_cliente']) || $data['id_cliente'] <= 0) {
            throw new InvalidArgumentException('O campo id_cliente deve ser um inteiro positivo.');
        }

        if (empty($data['id_veiculo']) || !is_int($data['id_veiculo']) || $data['id_veiculo'] <= 0) {
            throw new InvalidArgumentException('O campo id_veiculo deve ser um inteiro positivo.');
        }

        return new CriarOrdemServicoInputDTO(
            idCliente: $data['id_cliente'],
            idVeiculo: $data['id_veiculo'],
        );
    }
}

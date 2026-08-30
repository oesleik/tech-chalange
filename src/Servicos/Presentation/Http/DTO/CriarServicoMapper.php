<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\DTO;

use App\Servicos\Application\UseCase\CriarServico\CriarServicoInputDTO;
use InvalidArgumentException;

final class CriarServicoMapper {
    /** @param array<string, mixed> $data */
    public static function parse(array $data): CriarServicoInputDTO {
        if (empty($data['descricao']) || !is_string($data['descricao'])) {
            throw new InvalidArgumentException('Descrição é obrigatória.');
        }
        if (!isset($data['valor_unitario']) || !is_numeric($data['valor_unitario'])) {
            throw new InvalidArgumentException('Valor unitário é obrigatório e deve ser numérico.');
        }
        if ((float) $data['valor_unitario'] < 0) {
            throw new InvalidArgumentException('Valor unitário não pode ser negativo.');
        }

        return new CriarServicoInputDTO(
            descricao: trim($data['descricao']),
            valorUnitario: (float) $data['valor_unitario'],
        );
    }
}

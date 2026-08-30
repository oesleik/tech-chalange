<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\DTO;

use App\Servicos\Application\UseCase\EditarServico\EditarServicoInputDTO;
use InvalidArgumentException;

final class EditarServicoMapper {
    /** @param array<string, mixed> $data */
    public static function parse(array $data): EditarServicoInputDTO {
        $descricao = null;
        if (array_key_exists('descricao', $data) && $data['descricao'] !== null) {
            if (!is_string($data['descricao'])) {
                throw new InvalidArgumentException('Descrição deve ser string.');
            }
            $descricao = trim($data['descricao']);
            if ($descricao === '') {
                throw new InvalidArgumentException('Descrição não pode ser vazia.');
            }
        }

        $valorUnitario = null;
        if (array_key_exists('valor_unitario', $data) && $data['valor_unitario'] !== null) {
            if (!is_numeric($data['valor_unitario'])) {
                throw new InvalidArgumentException('Valor unitário deve ser numérico.');
            }
            $valorUnitario = (float) $data['valor_unitario'];
            if ($valorUnitario < 0) {
                throw new InvalidArgumentException('Valor unitário não pode ser negativo.');
            }
        }

        return new EditarServicoInputDTO($descricao, $valorUnitario);
    }
}

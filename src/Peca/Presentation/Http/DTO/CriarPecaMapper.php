<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\DTO;

use App\Peca\Application\UseCase\CriarPeca\CriarPecaInputDTO;
use InvalidArgumentException;

final class CriarPecaMapper {
    public static function parse(array $data): CriarPecaInputDTO {
        if (empty($data['descricao']) || !is_string($data['descricao'])) {
            throw new InvalidArgumentException('Descrição é obrigatória.');
        }
        if (!isset($data['valor_unitario']) || !is_numeric($data['valor_unitario'])) {
            throw new InvalidArgumentException('Valor unitário é obrigatório e deve ser numérico.');
        }
        if ((float) $data['valor_unitario'] < 0) {
            throw new InvalidArgumentException('Valor unitário não pode ser negativo.');
        }

        return new CriarPecaInputDTO(
            descricao: trim($data['descricao']),
            valorUnitario: (float) $data['valor_unitario'],
        );
    }
}
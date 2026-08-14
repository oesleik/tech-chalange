<?php

declare(strict_types=1);

namespace App\Estoque\Presentation\Http\DTO;

use App\Estoque\Application\UseCase\RegistrarEntradaEstoque\RegistrarEntradaEstoqueInputDTO;

// só parsing e validação de formato — sem regra de negócio aqui
final class RegistrarEntradaEstoqueMapper {
    public static function parse(array $data): RegistrarEntradaEstoqueInputDTO {
        if (empty($data['id_peca']) || !is_int($data['id_peca']) || $data['id_peca'] <= 0) {
            throw new \InvalidArgumentException('O campo id_peca deve ser um inteiro positivo.');
        }
        if (empty($data['quantidade']) || !is_int($data['quantidade']) || $data['quantidade'] <= 0) {
            throw new \InvalidArgumentException('O campo quantidade deve ser um inteiro positivo.');
        }

        return new RegistrarEntradaEstoqueInputDTO($data['id_peca'], $data['quantidade']);
    }
}

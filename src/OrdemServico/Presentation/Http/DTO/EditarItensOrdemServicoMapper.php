<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\DTO;

use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarItensInputDTO;
use InvalidArgumentException;

final class EditarItensOrdemServicoMapper {
    /** @param string $campo  'id_peca' ou 'id_servico' */
    public static function parse(array $data, string $campo, int $idOrdemServico, bool $substituir): EditarItensInputDTO {
        $listaChave = str_starts_with($campo, 'id_peca') ? 'pecas' : 'servicos';

        if (!isset($data[$listaChave]) || !is_array($data[$listaChave])) {
            throw new InvalidArgumentException("O campo '{$listaChave}' deve ser um array.");
        }

        $itens = [];
        foreach ($data[$listaChave] as $item) {
            if (empty($item[$campo]) || !is_int($item[$campo]) || $item[$campo] <= 0) {
                throw new InvalidArgumentException("Cada item deve ter '{$campo}' positivo.");
            }
            if (empty($item['quantidade']) || !is_int($item['quantidade']) || $item['quantidade'] <= 0) {
                throw new InvalidArgumentException("Cada item deve ter 'quantidade' positiva.");
            }
            $itens[] = ['id' => $item[$campo], 'quantidade' => $item['quantidade']];
        }

        return new EditarItensInputDTO(
            idOrdemServico: $idOrdemServico,
            itens: $itens,
            substituir: $substituir,
        );
    }
}

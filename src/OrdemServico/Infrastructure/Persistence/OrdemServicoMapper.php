<?php

declare(strict_types=1);

namespace App\OrdemServico\Infrastructure\Persistence;

use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use DateTime;

final class OrdemServicoMapper {
    /** @param array<string, mixed> $linha */
    public static function paraEntidade(array $linha): OrdemServico {
        return new OrdemServico(
            id: (int) $linha['id'],
            idCliente: (int) $linha['id_cliente'],
            idVeiculo: (int) $linha['id_veiculo'],
            situacao: SituacaoOrdemServicoEnum::from($linha['situacao']),
            valorTotal: (float) ($linha['valor_total'] ?? 0),
            dataSolicitacao: new DateTime($linha['data_solicitacao']),
            dataAprovacao: isset($linha['data_aprovacao']) && $linha['data_aprovacao'] !== null
                ? new DateTime($linha['data_aprovacao'])
                : null,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\ValueObject\SituacaoOrdemValue;
use App\OrdemServico\ValueObject\ValorTotalValue;
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

readonly class CriarOrdemServicoRequest extends AbstractContract {
    public function __construct(
        public int $id_cliente,
        public int $id_veiculo,
    ) {}


    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'id_cliente' => [
                new Assert\NotBlank(),
                new Assert\Positive(),
            ],
            'id_veiculo' => [
                new Assert\NotBlank(),
                new Assert\Positive(),
            ],
        ]);
    }

    public function toOrdemServicoModel(): OrdemServicoModel {
        return new OrdemServicoModel(
            id: 0,
            idCliente: $this->id_cliente,
            idVeiculo: $this->id_veiculo,
            situacao: new SituacaoOrdemValue('Recebida'),
            valorTotal: new ValorTotalValue(0),
            dataSolicitacao: new DateTime(),
        );
    }
}

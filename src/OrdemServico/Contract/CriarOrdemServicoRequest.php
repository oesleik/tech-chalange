<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\ValueObject\ValorTotalValue;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;
use DateTime;

#[OA\Schema]
readonly class CriarOrdemServicoRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 456)]
        public int $id_cliente,
        #[OA\Property(example: 789)]
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
            situacao: SituacaoOrdemServicoEnum::RECEBIDA,
            valorTotal: new ValorTotalValue(0),
            dataSolicitacao: new DateTime(),
        );
    }
}

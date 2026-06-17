<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;

readonly class AtualizarSituacaoRequest extends AbstractContract {
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [
            'Recebida',
            'EmDiagnostico',
            'AguardandoAprovacao',
            'Aprovada',
            'Rejeitada',
            'EmExecucao',
            'Finalizada',
            'Entregue',
        ])]
        public string $situacao,
    ) {}
}

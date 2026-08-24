<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Router;

use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController;
use Psr\Http\Message\ResponseInterface;

final class AtualizarSituacaoRouter {
    public function __construct(private readonly AtualizarSituacaoController $controller) {}

    public function atualizarParaEmDiagnostico(int $id, ResponseInterface $response): ResponseInterface {
        return $this->controller->atualizarParaEmDiagnostico($id, $response);
    }

    public function atualizarParaAguardandoAprovacao(int $id, ResponseInterface $response): ResponseInterface {
        return $this->controller->atualizarParaAguardandoAprovacao($id, $response);
    }

    public function atualizarParaEmExecucao(int $id, ResponseInterface $response): ResponseInterface {
        return $this->controller->atualizarParaEmExecucao($id, $response);
    }

    public function atualizarParaFinalizada(int $id, ResponseInterface $response): ResponseInterface {
        return $this->controller->atualizarParaFinalizada($id, $response);
    }

    public function atualizarParaEntregue(int $id, ResponseInterface $response): ResponseInterface {
        return $this->controller->atualizarParaEntregue($id, $response);
    }
}

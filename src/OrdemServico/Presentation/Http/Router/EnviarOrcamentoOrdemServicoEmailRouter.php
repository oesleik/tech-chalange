<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Router;

use App\OrdemServico\Presentation\Http\Controller\EnviarOrcamentoOrdemServicoEmailController;
use Psr\Http\Message\ResponseInterface;

final class EnviarOrcamentoOrdemServicoEmailRouter {
    public function __construct(private readonly EnviarOrcamentoOrdemServicoEmailController $controller) {}

    public function __invoke(int $id, ResponseInterface $response): ResponseInterface {
        return ($this->controller)($id, $response);
    }
}

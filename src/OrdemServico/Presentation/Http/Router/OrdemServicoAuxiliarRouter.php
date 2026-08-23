<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Router;

use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController;
use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoEmailController;
use App\OrdemServico\Presentation\Http\Controller\ConsultarOrdemServicoPorVeiculoEClienteController;
use App\OrdemServico\Presentation\Http\Controller\EditarItensOrdemServicoController;
use App\OrdemServico\Presentation\Http\Controller\EnviarOrcamentoOrdemServicoEmailController;
use App\OrdemServico\Presentation\Http\Controller\RelatoriosOrdemServicoController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OrdemServicoAuxiliarRouter {
    public function __construct(
        public readonly AtualizarSituacaoController $atualizarSituacao,
        public readonly AtualizarSituacaoEmailController $atualizarSituacaoEmail,
        public readonly EditarItensOrdemServicoController $editarItens,
        public readonly EnviarOrcamentoOrdemServicoEmailController $enviarOrcamento,
        public readonly RelatoriosOrdemServicoController $relatorios,
        public readonly ConsultarOrdemServicoPorVeiculoEClienteController $consulta,
    ) {}
}

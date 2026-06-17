<?php

declare(strict_types=1);

namespace App\OrdemServico\Model;

enum SituacaoOrdemServicoEnum: string {
    case RECEBIDA = 'Recebida';
    case EM_DIAGNOSTICO = 'EmDiagnostico';
    case AGUARDANDO_APROVACAO = 'AguardandoAprovacao';
    case APROVADA = 'Aprovada';
    case REJEITADA = 'Rejeitada';
    case EM_EXECUCAO = 'EmExecucao';
    case FINALIZADA = 'Finalizada';
    case ENTREGUE = 'Entregue';
}

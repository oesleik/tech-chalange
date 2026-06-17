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

    public function getFormattedValue(): string {
        return match ($this) {
            self::RECEBIDA => 'recebida',
            self::EM_DIAGNOSTICO => 'em diagnóstico',
            self::AGUARDANDO_APROVACAO => 'aguardando aprovação',
            self::APROVADA => 'aprovada',
            self::REJEITADA => 'rejeitada',
            self::EM_EXECUCAO => 'em execução',
            self::FINALIZADA => 'finalizada',
            self::ENTREGUE => 'entregue',
        };
    }

    public function podeAlterarSituacao(self $novaSituacao): bool {
        $situacoesPermitidas = match ($novaSituacao) {
            self::EM_DIAGNOSTICO => [self::RECEBIDA],
            self::AGUARDANDO_APROVACAO => [self::RECEBIDA, self::EM_DIAGNOSTICO, self::APROVADA, self::REJEITADA, self::EM_EXECUCAO],
            self::APROVADA => [self::AGUARDANDO_APROVACAO],
            self::REJEITADA => [self::AGUARDANDO_APROVACAO],
            self::EM_EXECUCAO => [self::RECEBIDA, self::EM_DIAGNOSTICO, self::APROVADA],
            self::FINALIZADA => [self::EM_EXECUCAO],
            self::ENTREGUE => [self::FINALIZADA, self::REJEITADA],
            default => [],
        };

        return in_array($this, $situacoesPermitidas, true);
    }

    public function deveModificarDataAprovacao(): bool {
        return in_array($this, [self::APROVADA, self::REJEITADA], true);
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\ValueObject;

class SituacaoOrdemValue
{
    private const SITUACOES_VALIDAS = [
        'Recebida',
        'EmDiagnostico',
        'AguardandoAprovacao',
        'Aprovada',
        'Rejeitada',
        'EmExecucao',
        'Finalizada',
        'Entregue',
    ];

    public function __construct(
        private string $situacao
    ) {
        if (!in_array($situacao, self::SITUACOES_VALIDAS)) {
            throw new \InvalidArgumentException("Situação inválida: {$situacao}");
        }
    }

    public function getValue(): string
    {
        return $this->situacao;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public static function validaSituacoes(): array
    {
        return self::SITUACOES_VALIDAS;
    }
}

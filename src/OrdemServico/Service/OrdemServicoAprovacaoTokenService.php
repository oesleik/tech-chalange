<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\Core\Auth\JwtService;

class OrdemServicoAprovacaoTokenService {
    private const TTL_SEGUNDOS = 60 * 60 * 24 * 7; // 1 semana

    public function __construct(
        private JwtService $jwtService
    ) {}

    public function gerarToken(int $idOrdemServico): string {
        return $this->jwtService->generate(
            ['id_ordem_servico' => $idOrdemServico],
            self::TTL_SEGUNDOS
        );
    }
}

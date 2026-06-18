<?php

declare(strict_types=1);

namespace App\Core\Auth\OrdemServico;

use App\Core\Auth\AbstractJwtService;
use App\Core\Config\JwtOrdemServicoConfig;

class JwtOrdemServicoService extends AbstractJwtService {
    public function __construct(JwtOrdemServicoConfig $jwtConfig) {
        parent::__construct($jwtConfig);
    }
}

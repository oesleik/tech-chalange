<?php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Config\JwtConfig;

class JwtService extends AbstractJwtService {
    public function __construct(JwtConfig $jwtConfig) {
        parent::__construct($jwtConfig);
    }
}

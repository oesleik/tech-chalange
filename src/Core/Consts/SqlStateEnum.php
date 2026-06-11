<?php

declare(strict_types=1);

namespace App\Core\Consts;

enum SqlStateEnum: string {
    case TABLE_NOT_FOUND = "42S02";
}

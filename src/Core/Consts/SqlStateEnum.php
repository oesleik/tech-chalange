<?php

declare(strict_types=1);

namespace App\Core\Consts;

enum SqlStateEnum: string {
    case TABLE_NOT_FOUND = "42S02";
    case DUPLICATE_ENTRY = "23000";
}

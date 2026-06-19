<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDOException;

enum DatabaseErrorEnum: int {
    case UNKNOWN_ERROR = 0;
    case DUPLICATE_ENTRY = 1062;
    case NO_REFERENCED_ROW = 1452;
    case ROW_IS_REFERENCED = 1451;
    case TABLE_DOES_NOT_EXIST = 1146;
    case UNKNOWN_COLUMN = 1054;
    case ACCESS_DENIED = 1045;
    case BAD_DATABASE = 1049;
    case LOCK_WAIT_TIMEOUT = 1205;
    case DEADLOCK_FOUND = 1213;
    case DATA_TOO_LONG = 1406;
    case CANNOT_BE_NULL = 1048;
    case SYNTAX_ERROR = 1064;

    public static function fromPdoException(PDOException $e): ?static {
        $errorCode = is_array($e->errorInfo) && isset($e->errorInfo[1]) ? intval($e->errorInfo[1]) : 0;
        return self::tryFrom($errorCode);
    }
}

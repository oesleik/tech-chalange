<?php

declare(strict_types=1);

namespace App\Core\Database;

interface TransactionInterface {
    public function commit(): void;
    public function rollback(): void;
}

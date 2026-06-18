<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;

class CurrentTransaction implements TransactionInterface {
    public function __construct(
        private PDO $pdo,
    ) {}

    public function commit(): void {
        $this->pdo->commit();
    }

    public function rollback(): void {
        $this->pdo->rollback();
    }
}

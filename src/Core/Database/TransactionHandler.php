<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;

class TransactionHandler {
    public function beginTransaction(PDO $pdo): TransactionInterface {
        if ($pdo->inTransaction()) {
            return new FakeTransaction();
        }

        $pdo->beginTransaction();
        return new CurrentTransaction($pdo);
    }
}

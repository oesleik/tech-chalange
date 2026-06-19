<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;

class FakeTransaction implements TransactionInterface {
    public function commit(): void {
        // do nothing
    }

    public function rollback(): void {
        // do nothing
    }
}

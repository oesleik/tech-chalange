<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Core\Config\DatabaseConfig;
use PHPUnit\Framework\TestCase;

class AppDatabaseTest extends TestCase {
    public function testInstance(): void {
        $this->expectNotToPerformAssertions();
        $pdo = new AppDatabase(new DatabaseConfig());
    }
}

<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Core\Config\DatabaseConfig;
use PHPUnit\Framework\TestCase;

class AppDatabaseTest extends TestCase {
    public function testInstance(): void {
        $this->expectNotToPerformAssertions();

        new class (new DatabaseConfig()) extends AppDatabase {
            protected function startPdo(
                #[SensitiveParameter]
                string $dsn,
                #[SensitiveParameter]
                ?string $username,
                #[SensitiveParameter]
                ?string $password,
                #[SensitiveParameter]
                ?array $options,
            ): void {
                // Do nothing...
            }
        };
    }
}

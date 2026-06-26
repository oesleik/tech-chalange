<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Config\DatabaseConfig;
use PDO;
use SensitiveParameter;

class AppDatabase extends PDO {
    public function __construct(DatabaseConfig $config) {
        $this->startPdo(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config->getHost(),
                $config->getPort(),
                $config->getDatabase(),
            ),
            $config->getUsername(),
            $config->getPassword(),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }

	protected function startPdo(
		#[SensitiveParameter] string $dsn,
		#[SensitiveParameter] ?string $username,
		#[SensitiveParameter] ?string $password,
		#[SensitiveParameter] ?array $options,
	): void {
		parent::__construct($dsn, $username, $password, $options);
	}
}

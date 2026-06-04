<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class AppDatabase extends PDO {

	public function __construct() {
		parent::__construct(
			sprintf(
				'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
				$_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?? 'mysql',
				$_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?? '3306',
				$_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? 'app_db'
			),
			$_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? 'app_user',
			$_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? 'secret',
			[
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false,
			]
		);
	}

}

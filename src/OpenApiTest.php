<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class OpenApiTest extends TestCase {
	public function testReturnsSlimApp(): void {
		$app = require_once __DIR__ . "/openapi.php";
		$this->assertTrue(class_exists(\App\openapi::class));
	}
}

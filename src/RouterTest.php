<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase {
	public function testReturnsSlimApp(): void {
		$app = require_once __DIR__ . "/router.php";
		$this->assertInstanceOf(\Slim\App::class, $app);
	}
}

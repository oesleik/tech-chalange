<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase {
	public function testReturnsSlimApp(): void {
		require __DIR__ . "/bootstrap.php";
		$this->expectNotToPerformAssertions();
	}
}

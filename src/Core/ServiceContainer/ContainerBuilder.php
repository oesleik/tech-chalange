<?php

declare(strict_types=1);

namespace App\Core\ServiceContainer;

class ContainerBuilder {

	private \DI\ContainerBuilder $builder;

	public function __construct() {
		$this->builder = new \DI\ContainerBuilder();
		$this->builder->useAutowiring(true);
		$this->builder->useAttributes(false);
		$this->builder->addDefinitions(__DIR__ . "/definitions.core.php");
	}

	public function build(): \DI\Container {
		return $this->builder->build();
	}

}

<?php

declare(strict_types=1);

namespace App\Core;

class ContainerBuilder {

	private \DI\ContainerBuilder $builder;

	public function __construct() {
		$this->builder = new \DI\ContainerBuilder();
		$this->builder->useAutowiring(true);
		$this->builder->useAttributes(false);
	}

	public function build(): \DI\Container {
		return $this->builder->build();
	}

}

<?php

declare(strict_types=1);

namespace App\Core;

class ServiceContainerBuilder {
    private \DI\ContainerBuilder $builder;

    public function __construct() {
        $this->builder = new \DI\ContainerBuilder();
        $this->builder->useAutowiring(true);
        $this->builder->useAttributes(false);

        $defsDir = __DIR__ . "/../configs/service-container";
        $this->builder->addDefinitions($defsDir . "/core.definitions.php");
    }

    public function build(): \DI\Container {
        return $this->builder->build();
    }
}

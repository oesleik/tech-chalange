<?php

declare(strict_types=1);

namespace App\Core\Contract;

use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractContract {

	public function toArray(): array {
		return get_object_vars($this);
	}

	public static function getConstraints(): Assert\Collection {
		return new Assert\Collection();
	}

}

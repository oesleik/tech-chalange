<?php

declare(strict_types=1);

namespace App\Core\Contract;

use Symfony\Component\Validator\Constraints as Assert;

readonly abstract class AbstractContract {

	public static function getConstraints(): Assert\Collection {
		return new Assert\Collection();
	}

}

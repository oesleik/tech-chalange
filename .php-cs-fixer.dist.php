<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@auto' => true,
        '@auto:risky' => true,
		'braces_position' => [
			'classes_opening_brace' => 'same_line',
			'functions_opening_brace' => 'same_line'
		],
    ])
    ->setFinder((new Finder())->in(__DIR__))
;

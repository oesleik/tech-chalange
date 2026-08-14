<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
$app = require_once __DIR__ . '/../src/router.php';

if (!$app instanceof \Slim\App) {
    throw new \Error("Unexpected app type, expecting a Slim App");
}

$app->run();

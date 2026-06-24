<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ResponseFactory;

return [
    ResponseInterface::class => fn() => new ResponseFactory()->createResponse(),
];

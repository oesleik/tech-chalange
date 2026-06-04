<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\AppDatabase;
use Psr\Http\Message\ResponseInterface;

class BaseController {

	public function index(ResponseInterface $response): ResponseInterface {
		$response->getBody()->write(json_encode([
			'status'  => 'ok',
			'message' => 'API disponível',
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

	public function health(ResponseInterface $response, AppDatabase $db): ResponseInterface {
		$db->query('SELECT 1');

		$response->getBody()->write(json_encode([
			'status'   => 'ok',
			'database' => 'connected',
		]));

		return $response->withHeader('Content-Type', 'application/json');
	}

}

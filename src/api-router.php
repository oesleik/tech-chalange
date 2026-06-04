<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?? 'mysql',
        $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?? '3306',
        $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? 'app_db'
    ),
    $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? 'app_user',
    $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? 'secret',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

// -------------------------------------------------------
// Slim App
// -------------------------------------------------------
$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->addErrorMiddleware(
    displayErrorDetails: true,   // false em produção
    logErrors: true,
    logErrorDetails: true
);

// -------------------------------------------------------
// Rotas de exemplo
// -------------------------------------------------------

// GET /
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'status'  => 'ok',
        'message' => 'Slim 4 + PHP 8.4 + MySQL 9 rodando!',
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// GET /health — verifica conexão com o banco
$app->get('/health', function (Request $request, Response $response) use ($pdo) {
    $pdo->query('SELECT 1');
    $response->getBody()->write(json_encode([
        'status'   => 'ok',
        'database' => 'connected',
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();

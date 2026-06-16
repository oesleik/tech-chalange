#!/usr/bin/env php
<?php

declare(strict_types=1);

// Carrega o .env manualmente (sem autoloader para evitar conflito de versão PHP)
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $_ENV[trim($key)] = trim($value);
    }
}

$secret  = $_ENV['JWT_SECRET']  ?? null;
$ttl     = (int) ($_ENV['JWT_TTL']     ?? 3600);
$issuer  = $_ENV['JWT_ISSUER']  ?? 'tech-challenge-api';

if (empty($secret)) {
    fwrite(STDERR, "ERRO: JWT_SECRET não definido no .env\n");
    exit(1);
}

$opts    = getopt('', ['user-id:', 'role:', 'ttl:']);
$userId  = isset($opts['user-id']) ? (int) $opts['user-id'] : 1;
$role    = $opts['role'] ?? 'api_user';
$ttl     = isset($opts['ttl']) ? (int) $opts['ttl'] : $ttl;
$now     = time();

$header    = b64u(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
$payload   = b64u(json_encode([
    'iss'     => $issuer,
    'iat'     => $now,
    'exp'     => $now + $ttl,
    'user_id' => $userId,
    'role'    => $role,
]));
$signature = b64u(hash_hmac('sha256', "$header.$payload", $secret, true));
$token     = "$header.$payload.$signature";

echo "\n";
echo "TOKEN:\n$token\n\n";
echo "user_id : $userId\n";
echo "role    : $role\n";
echo "expira  : " . date('Y-m-d H:i:s', $now + $ttl) . "\n\n";
echo "CURL:\n";
echo "curl -H \"Authorization: Bearer $token\" http://localhost/clientes/\n\n";

function b64u(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
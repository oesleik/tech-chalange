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

$secret = $_ENV['JWT_SECRET_ORDEM_SERVICO'] ?? null;
$issuer = $_ENV['JWT_ISSUER_ORDEM_SERVICO'] ?? 'tech-challenge-api';

if (empty($secret)) {
    fwrite(STDERR, "ERRO: JWT_SECRET_ORDEM_SERVICO não definido no .env\n");
    exit(1);
}

$opts    = getopt('', ['id_ordem_servico:', 'ttl:']);
$ttl     = isset($opts['ttl']) ? (int) $opts['ttl'] : 60 * 60 * 24 * 7; // 1 semana

$ordemId = $opts['id_ordem_servico'] ?? null;
if ($ordemId === null) {
    echo "Id da Ordem de Serviço: ";
    $ordemId = trim(fgets(STDIN));
}

if (!ctype_digit($ordemId)) {
    fwrite(STDERR, "ERRO: id da Ordem de Serviço deve ser um número inteiro.\n");
    exit(1);
}

$ordemId = (int) $ordemId;
$now     = time();

$header    = b64u(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
$payload   = b64u(json_encode([
    'iss'              => $issuer,
    'iat'              => $now,
    'exp'              => $now + $ttl,
    'id_ordem_servico' => $ordemId,
]));
$signature = b64u(hash_hmac('sha256', "$header.$payload", $secret, true));
$token     = "$header.$payload.$signature";

echo "\n";
echo "TOKEN:\n$token\n\n";
echo "id_ordem_servico : $ordemId\n";
echo "expira           : " . date('Y-m-d H:i:s', $now + $ttl) . "\n\n";
echo "CURL:\n";
echo "curl -X PUT -H \"Authorization: Bearer $token\" http://localhost/ordens-servico\n\n";

function b64u(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
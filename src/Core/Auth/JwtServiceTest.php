<?php

declare(strict_types=1);

namespace Tests\Core\Auth;

use App\Core\Auth\JwtException;
use App\Core\Auth\JwtService;
use App\Core\Config\JwtConfig;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase {
    private const SECRET = 'segredo-de-teste-com-pelo-menos-32-bytes';

    public function testValidaTokenGeradoComOMesmoSegredo(): void {
        $service = $this->criarService(self::SECRET);

        $claims = $service->validate($service->generate(['role' => 'cliente']));

        $this->assertSame('cliente', $claims['role']);
    }

    public function testRejeitaTokenAssinadoComOutroSegredo(): void {
        $token = $this->criarService('outro-segredo-qualquer-com-32-bytes-ou-mais')->generate();

        $this->expectException(JwtException::class);

        $this->criarService(self::SECRET)->validate($token);
    }

    public function testRejeitaTokenComPayloadAdulterado(): void {
        $service = $this->criarService(self::SECRET);
        [$header, , $signature] = explode('.', $service->generate(['role' => 'cliente']));

        $payload = rtrim(strtr(base64_encode((string) json_encode([
            'iss'  => 'tech-challenge-api',
            'iat'  => time(),
            'exp'  => time() + 3600,
            'role' => 'admin',
        ])), '+/', '-_'), '=');

        $this->expectException(JwtException::class);

        $service->validate("{$header}.{$payload}.{$signature}");
    }

    private function criarService(string $secret): JwtService {
        $config = $this->createStub(JwtConfig::class);
        $config->method('getSecret')->willReturn($secret);
        $config->method('getIssuer')->willReturn('tech-challenge-api');
        $config->method('getTtl')->willReturn(3600);

        return new JwtService($config);
    }
}

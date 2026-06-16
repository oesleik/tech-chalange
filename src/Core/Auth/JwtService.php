<?php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Auth\SystemClock as AuthSystemClock;
use App\Core\Config\JwtConfig;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;

class JwtService {
    private Configuration $config;

    public function __construct(private readonly JwtConfig $jwtConfig) {
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->jwtConfig->getSecret()),
        );

        $clock = new AuthSystemClock();

        $this->config = $this->config->withValidationConstraints(
            new IssuedBy($this->jwtConfig->getIssuer()),
            new LooseValidAt($clock),
        );
    }

    /** @param array<string, mixed> $claims */
    public function generate(array $claims = []): string {
        $now = new DateTimeImmutable();

        $builder = $this->config->builder()
            ->issuedBy($this->jwtConfig->getIssuer())
            ->issuedAt($now)
            ->expiresAt($now->modify("+{$this->jwtConfig->getTtl()} seconds"));

        foreach ($claims as $key => $value) {
            $builder = $builder->withClaim($key, $value);
        }

        return $builder
            ->getToken($this->config->signer(), $this->config->signingKey())
            ->toString();
    }

    /** @return array<string, mixed> */
    public function validate(string $tokenString): array {
        try {
            $token = $this->config->parser()->parse($tokenString);
        } catch (\Throwable) {
            throw new JwtException('Token mal formado.');
        }

        if (!($token instanceof Plain)) {
            throw new JwtException('Tipo de token inválido.');
        }

        $constraints = $this->config->validationConstraints();

        if (!$this->config->validator()->validate($token, ...$constraints)) {
            throw new JwtException('Token inválido ou expirado.');
        }

        return $token->claims()->all();
    }
}
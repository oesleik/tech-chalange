import { createHmac } from 'node:crypto';

// HS256 compatível com src/Core/Auth/AbstractJwtService.php (lcobucci/jwt): mesmo segredo
// (JWT_SECRET) e emissor (JWT_ISSUER), validados pelo JwtMiddleware nas rotas protegidas.

export function gerarJwt(claims, { secret, issuer, ttl, agora = Math.floor(Date.now() / 1000) }) {
    const header = base64url(JSON.stringify({ alg: 'HS256', typ: 'JWT' }));
    const payload = base64url(JSON.stringify({ iss: issuer, iat: agora, exp: agora + ttl, ...claims }));
    const signature = createHmac('sha256', secret).update(`${header}.${payload}`).digest('base64url');

    return `${header}.${payload}.${signature}`;
}

function base64url(value) {
    return Buffer.from(value).toString('base64url');
}
